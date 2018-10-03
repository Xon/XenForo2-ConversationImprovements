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
 * Extends \XF\Entity\ConversationMessage
 *
 * @property int last_edit_date
 * @property int last_edit_user_id
 * @property int edit_count
 * @property string message_
 */
class ConversationMessage extends XFCP_ConversationMessage
{
    /**
     * @param string $error
     *
     * @return bool
     */
    public function canViewHistory(/** @noinspection PhpUnusedParameterInspection */ &$error = null)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id) {
            return false;
        }

        if (!$this->app()->options()->editHistory['enabled']) {
            return false;
        }

        if ($visitor->hasPermission('conversation', 'editAnyMessage')) {
            return true;
        }

        if ($visitor->hasPermission('conversation', 'sv_manageConversation')) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFirstMessage()
    {
        $conversation = $this->Conversation;
        if (!$conversation) {
            return false;
        }

        if ($this->message_id == $conversation->first_message_id) {
            return true;
        }

        // this can be called during a conversation insert, assume first message
        if (!$conversation->conversation_id) {
            return true;
        }

        if (
            !$conversation->first_message_id
            && ($this->message_date == $conversation->start_date)
        ) {
            return true;
        }

        return false;
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
                'conversation_id',
                'message_date',
                'user_id',
                'message'
            ]
        ];

        return $structure;
    }
}
