<?php

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
            'content_id'   => $params->get('conversation_id')
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
            'content_id'   => $params->get('message_id')
        ]);
    }
}
