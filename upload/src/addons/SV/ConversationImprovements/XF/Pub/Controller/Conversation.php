<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\XF\Pub\Controller;

use SV\ConversationImprovements\XF\Entity\ConversationMessage as ExtendedConversationMessageEntity;
use XF\Entity\ConversationMessage as ConversationMessageEntity;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Service\Conversation\MessageEditor;

/**
 * @extends \XF\Pub\Controller\Conversation
 */
class Conversation extends XFCP_Conversation
{
    /**
     * @param ParameterBag $params
     * @return AbstractReply
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
     * @return AbstractReply
     */
    public function actionMessagesHistory(ParameterBag $params)
    {
        return $this->rerouteController('XF:EditHistory', 'index', [
            'content_type' => 'conversation_message',
            'content_id'   => $params->get('message_id'),
        ]);
    }

    /**
     * @param ConversationMessageEntity|ExtendedConversationMessageEntity $conversationMessage
     * @return MessageEditor
     */
    protected function setupMessageEdit(ConversationMessageEntity $conversationMessage)
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
