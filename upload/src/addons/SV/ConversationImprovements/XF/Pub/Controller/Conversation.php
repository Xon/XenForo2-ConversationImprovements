<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

/**
 * @extends \XF\Pub\Controller\Conversation
 */
class Conversation extends XFCP_Conversation
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function actionHistory(ParameterBag $params)
    {
        return $this->rerouteController('XF:EditHistory', 'index', [
            'content_type' => 'conversation',
            'content_id'   => $params->get('conversation_id'),
        ]);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function actionMessagesHistory(ParameterBag $params)
    {
        return $this->rerouteController('XF:EditHistory', 'index', [
            'content_type' => 'conversation_message',
            'content_id'   => $params->get('message_id'),
        ]);
    }

    /**
     * @param \XF\Entity\ConversationMessage|\SV\ConversationImprovements\XF\Entity\ConversationMessage $conversationMessage
     *
     * @return \XF\Service\Conversation\MessageEditor
     */
    protected function setupMessageEdit(\XF\Entity\ConversationMessage $conversationMessage)
    {
        $last_edit_date = $conversationMessage->last_edit_date;
        $last_edit_user_id = $conversationMessage->last_edit_user_id;

        $editor = parent::setupMessageEdit($conversationMessage);

        if ($conversationMessage->canEditSilently())
        {
            $silentEdit = $this->filter('silent', 'bool');
            if ($silentEdit)
            {
                $conversationMessage->last_edit_date = $last_edit_date;
                $conversationMessage->last_edit_user_id = $last_edit_user_id;
                if ($this->filter('clear_edit', 'bool'))
                {
                    $conversationMessage->last_edit_date = 0;
                }
            }
        }

        return $editor;
    }
}
