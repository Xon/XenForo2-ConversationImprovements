<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\XF\Entity;

use SV\SearchImprovements\Search\Features\ISearchableDiscussionUser;
use XF\Mvc\Entity\Structure;
use function array_keys;
use function array_unique;
use function array_values;

/**
 * Extends \XF\Entity\ConversationMaster
 *
 * @property int    last_edit_date
 * @property int    last_edit_user_id
 * @property int    edit_count
 * @property string title_
 *
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
     * @param \XF\Phrase|string|null $error
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
     * List of all user where once a member of a conversation
     *
     * @return int []
     * @noinspection PhpUnnecessaryLocalVariableInspection*/
    public function getSearchableRecipients(): array
    {
        $recipients = array_keys($this->recipients);
        $recipients[] = $this->user_id;
        // array_values ensures the value is encoded as a json array, and not a json hash if the php array is not a list
        $recipients = array_values(array_unique($recipients));

        return $recipients;
    }

    /**
     * @deprecated
     * @return int[]
     */
    public function getIndexableRecipients(): array
    {
        return $this->getDiscussionUserIds();
    }

    /**
     * List of users who can view a conversation
     * @return array<int>
     */
    public function getDiscussionUserIds(): array
    {
        $cache = $this->app()->cache();
        if ($cache)
        {
            $result = $cache->fetch('sv_searchable_recipients_' . $this->conversation_id);
            if (\is_array($result))
            {
                return $result;
            }
        }

        $results = $this->db()->fetchAllColumn('
            SELECT user_id
            FROM xf_conversation_recipient
            WHERE conversation_id = ?  AND recipient_state = ?
        ', [$this->conversation_id, 'active']);

        if ($cache)
        {
            $cache->save('sv_searchable_recipients_' . $this->conversation_id, $results, 60);
        }

        return $results;
    }

    /**
     * @return void
     */
    public function clearIndexableRecipientsCache()
    {
        $cache = $this->app()->cache();
        if ($cache)
        {
            $cache->delete('sv_searchable_recipients_' . $this->conversation_id);
        }
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $db = $this->db();
        $db->delete('xf_edit_history', 'content_id IN ? AND content_type = ?', [$this->conversation_id, 'conversation']);

        $messageIds = $this->message_ids;
        if ($messageIds)
        {
            $messageIdsQuoted = $db->quote($messageIds);
            $db->delete('xf_edit_history', 'content_id IN (' . $messageIdsQuoted . ') AND content_type = ?', 'conversation_message');
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

        return $structure;
    }
}
