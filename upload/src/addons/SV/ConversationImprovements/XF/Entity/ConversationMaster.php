<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\XF\Entity;

use SV\SearchImprovements\Search\Features\ISearchableDiscussionUser;
use XF\Mvc\Entity\Structure;
use XF\Phrase;
use function array_keys;
use function array_unique;
use function array_values;

/**
 * @extends \XF\Entity\ConversationMaster
 * @property int    last_edit_date
 * @property int    last_edit_user_id
 * @property int    edit_count
 * @property string title_
 *
 * @property-read list<int> $recipient_user_ids
 * @property-read list<int> $active_recipient_user_ids
 * @property-read ConversationMessage $FirstMessage
 */
class ConversationMaster extends XFCP_ConversationMaster implements ISearchableDiscussionUser
{
    /**
     * @return bool
     */
    public function canEdit()
    {
        $visitor = \XF::visitor();
        if ($visitor->hasPermission('conversation', 'sv_manageConversation'))
        {
            return true;
        }

        return parent::canEdit();
    }

    /**
     * @return bool
     */
    public function canReply()
    {
        $visitor = \XF::visitor();
        if (!$visitor->hasPermission('conversation', 'canReply'))
        {
            return false;
        }

        $replyLimit = $visitor->hasPermission('conversation', 'replyLimit');
        if (($replyLimit != -1) && ($this->reply_count >= $replyLimit))
        {
            return false;
        }

        return parent::canReply();
    }

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

        if ($visitor->hasPermission('conversation', 'sv_manageConversation'))
        {
            return true;
        }

        return false;
    }

    /**
     * @deprecated
     */
    public function getSearchableRecipients(): array
    {
        return $this->getActiveRecipientUserIds();
    }

    /**
     * @return list<int>
     */
    public function getRecipientUserIds(): array
    {
        if (\XF::$versionId >= 2030000)
        {
            return parent::getRecipientUserIds();
        }

        return \XF::db()->fetchAllColumn(
            'SELECT user_id
				FROM xf_conversation_recipient
				WHERE conversation_id = ?',
            [$this->conversation_id]
        );
    }

    /**
     * @return list<int>
     */
    public function getActiveRecipientUserIds(): array
    {
        if (\XF::$versionId >= 2030000)
        {
            return parent::getActiveRecipientUserIds();
        }

        return $this->db()->fetchAllColumn(
            'SELECT user_id
				FROM xf_conversation_recipient
				WHERE conversation_id = ? AND recipient_state = ?',
            [$this->conversation_id, 'active']
        );
    }

    /**
     * List of users who can view a conversation
     * @return array<int>
     */
    public function getDiscussionUserIds(): array
    {
        return $this->db()->fetchAllColumn('
            SELECT user_id
            FROM xf_conversation_recipient
            WHERE conversation_id = ?  AND recipient_state = ?
        ', [$this->conversation_id, 'active']);
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $db = $this->db();
        $db->query('
            DELETE 
            FROM xf_edit_history 
            WHERE  content_type = ? AND content_id = ?
        ', ['conversation', $this->conversation_id]);

        $messageIds = $this->message_ids;
        if (count($messageIds) !== 0)
        {
            $db->query('
                DELETE 
                FROM xf_edit_history 
                WHERE  content_type = ? AND content_id in (' . $db->quote($messageIds) . ')
            ', ['conversation_message']);
        }
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
                'title',
                'user_id',
                'start_date',
                'first_message_id',
                'recipients',
            ],
        ];
        $structure->behaviors['XF:IndexableContainer'] = [
            'childContentType' => 'conversation_message',
            'childIds'         => function ($conversation) {
                /** @var \XF\Entity\ConversationMaster $conversation */
                return $conversation->message_ids;
            },
            'checkForUpdates'  => ['recipients'],
        ];

        if (\XF::$versionId < 2030000)
        {
            $structure->getters['recipient_user_ids'] = ['getter' => 'getRecipientUserIds', 'cache' => true];
			$structure->getters['active_recipient_user_ids'] = ['getter' => 'getActiveRecipientUserIds', 'cache' => true];
        }

        return $structure;
    }
}
