<?php

/*
 * This file is part of a XenForo add-on.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SV\ConversationImprovements\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\ConversationMaster
 *
 * @property int last_edit_date
 * @property int last_edit_user_id
 * @property int edit_count
 */
class ConversationMaster extends XFCP_ConversationMaster
{
    /**
     * @return bool
     */
    public function canEdit()
    {
        $visitor = \XF::visitor();
        if ($visitor->hasPermission('conversation', 'sv_manageConversation')) {
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
        if (!$visitor->hasPermission('conversation', 'canReply')) {
            return false;
        }

        $replyLimit = $visitor->hasPermission('conversation', 'replyLimit');
        if (($replyLimit != -1) && ($this->reply_count >= $replyLimit)) {
            return false;
        }

        return parent::canReply();
    }

    /**
     * @param string $error
     *
     * @return bool
     */
    public function canViewHistory(&$error = null)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id) {
            return false;
        }

        if (!$this->app()->options()->editHistory['enabled']) {
            return false;
        }

        if ($visitor->hasPermission('conversation', 'sv_manageConversation')) {
            return true;
        }

        return ($this->user_id == $visitor->user_id);
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['last_edit_date'] = [
            'type'    => self::UINT,
            'default' => 0
        ];
        $structure->columns['last_edit_user_id'] = [
            'type'    => self::UINT,
            'default' => 0
        ];
        $structure->columns['edit_count'] = [
            'type'    => self::UINT,
            'default' => 0,
            'forced'  => true
        ];
        $structure->behaviors['XF:Indexable'] = [
            'checkForUpdates' => [
                'title',
                'user_id',
                'start_date',
                'first_message_id',
                'recipients'
            ]
        ];
        $structure->behaviors['XF:IndexableContainer'] = [
            'childContentType' => 'conversation_message',
            'childIds'         => function ($conversation) {
                /** @var \XF\Entity\ConversationMaster $conversation */
                return $conversation->message_ids;
            },
            'checkForUpdates' => 'recipients'
        ];

        return $structure;
    }
}
