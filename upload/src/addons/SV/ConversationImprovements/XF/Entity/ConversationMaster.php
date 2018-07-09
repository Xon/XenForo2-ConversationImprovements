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
 */
class ConversationMaster extends XFCP_ConversationMaster
{
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

        if ($visitor->hasPermission('conversation', 'editAnyMessage')) {
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
