<?php

namespace SV\ConversationImprovements\EditHistory;

use XF\EditHistory\AbstractHandler;
use XF\Entity\EditHistory;
use XF\Mvc\Entity\Entity;

/**
 * An edit history handler for conversation messages.
 */
class ConversationMessage extends AbstractHandler
{
    /**
     * @param Entity $content
     * @return bool
     */
    public function canViewHistory(Entity $content)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $content */
        return ($content->canView() && $content->canViewHistory());
    }

    /**
     * @param Entity $content
     * @return bool
     */
    public function canRevertContent(Entity $content)
    {
        /** @var \XF\Entity\ConversationMessage $content */
        return $content->canEdit();
    }

    /**
     * @param Entity $content
     * @return string
     */
    public function getContentTitle(Entity $content)
    {
        /** @var \XF\Entity\ConversationMessage $content */
        return \XF::phrase('conversation_message_in_x', [
            'title' => $content->Conversation->title,
        ]);
    }

    /**
     * @param Entity $content
     * @return string
     */
    public function getContentText(Entity $content)
    {
        /** @var \XF\Entity\ConversationMessage $content */
        return $content->message;
    }

    /**
     * @param Entity $content
     * @return string
     */
    public function getContentLink(Entity $content)
    {
        return \XF::app()->router('public')->buildLink('conversations/messages', $content);
    }

    /**
     * @param Entity $content
     * @return array
     */
    public function getBreadcrumbs(Entity $content)
    {
        $router = \XF::app()->router('public');

        $breadcrumbs[] = [
            'value' => \XF::phrase('conversations'),
            'href'  => $router->buildLink('conversations'),
        ];

        /** @var \XF\Entity\ConversationMessage $content */
        $breadcrumbs[] = [
            'value' => $content->Conversation->title,
            'href'  => $router->buildLink('conversations/messages', $content),
        ];

        return $breadcrumbs;
    }

    /**
     * @param Entity      $content
     * @param EditHistory $history
     * @param EditHistory $previous
     * @return \XF\Entity\ConversationMessage
     */
    public function revertToVersion(Entity $content, EditHistory $history, EditHistory $previous = null)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $content */
        /** @var \SV\ConversationImprovements\XF\Service\Conversation\MessageEditor $editor */
        $editor = \XF::app()->service('XF:Conversation\MessageEditor', $content);

        $editor->logEdit(false);
        $editor->setMessageContent($history->old_text);

        if (!$previous || ($previous->edit_user_id !== $content->user_id))
        {
            $content->last_edit_date = 0;
        }
        else if ($previous && ($previous->edit_user_id === $content->user_id))
        {
            $content->last_edit_date = $previous->edit_date;
            $content->last_edit_user_id = $previous->edit_user_id;
        }

        return $editor->save();
    }

    /**
     * @param string $text
     * @param Entity $content
     * @return string
     */
    public function getHtmlFormattedContent($text, Entity $content = null)
    {
        $func = \XF::$versionId >= 2010370 ? 'func' : 'fn';

        return \XF::app()->templater()->$func('bb_code', [
            $text,
            'conversation_message',
            $content,
        ]);
    }

    /**
     * @return array
     */
    public function getEntityWith()
    {
        $visitor = \XF::visitor();

        return [
            'Conversation',
            "Conversation.Users|{$visitor->user_id}",
        ];
    }
}
