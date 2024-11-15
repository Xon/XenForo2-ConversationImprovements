<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\XF\Entity;

use XF\Mvc\Entity\Structure;
use XF\Phrase;
use function is_callable;

/**
 * @extends \XF\Entity\ConversationMessage
 *
 * @property int    last_edit_date
 * @property int    last_edit_user_id
 * @property int    edit_count
 * @property string message_
 *
 * @property-read ConversationMaster $Conversation
 */
class ConversationMessage extends XFCP_ConversationMessage
{
    /**
     * @param Phrase|string|null $error
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function canViewHistory(&$error = null)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return false;
        }

        if (!$this->app()->options()->editHistory['enabled'])
        {
            return false;
        }

        if ($visitor->hasPermission('conversation', 'editAnyMessage'))
        {
            return true;
        }

        if ($visitor->hasPermission('conversation', 'sv_manageConversation'))
        {
            return true;
        }

        return false;
    }

    public function isFirstMessage(): bool
    {
        if (is_callable(parent::class.'::isFirstMessage'))
        {
            return parent::isFirstMessage();
        }

        $conversation = $this->Conversation;
        if (!$conversation)
        {
            return false;
        }

        if ($this->message_id === $conversation->first_message_id)
        {
            return true;
        }

        // this can be called during a conversation insert, assume first message
        if (!$conversation->conversation_id)
        {
            return true;
        }

        if (
            !$conversation->first_message_id
            && ($this->message_date === $conversation->start_date)
        )
        {
            return true;
        }

        return false;
    }

    /** @noinspection PhpUnusedParameterInspection */
    public function canEditSilently(&$error = null): bool
    {
        $conversation = $this->Conversation;
        $visitor = \XF::visitor();
        if (!$visitor->user_id || !$conversation)
        {
            return false;
        }

        if (!$conversation->conversation_open)
        {
            $error = \XF::phraseDeferred('conversation_is_closed');
            return false;
        }

        if ($visitor->hasPermission('conversation', 'editAnyMessage'))
        {
            return true;
        }

        return false;
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $db = $this->db();
        $db->query('
            DELETE 
            FROM xf_edit_history 
            WHERE  content_type = ? AND content_id = ?
        ', ['conversation_message', $this->message_id]);
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['last_edit_date'] = ['type' => self::UINT, 'default' => 0,];
        $structure->columns['last_edit_user_id'] = ['type' => self::UINT, 'default' => 0,];
        $structure->columns['edit_count'] = ['type' => self::UINT, 'default' => 0, 'forced' => true,];
        $structure->behaviors['XF:Indexable'] = [
            'checkForUpdates' => [
                'conversation_id',
                'message_date',
                'user_id',
                'message',
            ],
        ];

        return $structure;
    }
}
