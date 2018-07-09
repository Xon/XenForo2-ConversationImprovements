<?php

/*
 * This file is part of a XenForo add-on.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SV\ConversationImprovements\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

/**
 * Extends \XF\Pub\Controller\Conversation
 */
class Conversation extends XFCP_Conversation
{
    /**
     * @param ParameterBag $params
     *
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function actionHistory(ParameterBag $params)
    {
        return $this->rerouteController('XF:EditHistory', 'index', [
            'content_type' => 'conversation',
            'content_id'   => $params->conversation_id
        ]);
    }

    /**
     * @param ParameterBag $params
     *
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function actionMessagesHistory(ParameterBag $params)
    {
        return $this->rerouteController('XF:EditHistory', 'index', [
            'content_type' => 'conversation_message',
            'content_id'   => $params->message_id
        ]);
    }
}
