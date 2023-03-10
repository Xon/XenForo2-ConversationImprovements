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
    public function canViewHistory(Entity $content): bool
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $content */
        return $content->canView() && $content->canViewHistory();
    }

    public function canRevertContent(Entity $content): bool
    {
        /** @var \XF\Entity\ConversationMessage $content */
        return $content->canEdit();
    }

    public function getContentTitle(Entity $content): \XF\Phrase
    {
        /** @var \XF\Entity\ConversationMessage $content */
        return \XF::phrase('conversation_message_in_x', [
            'title' => $content->Conversation->title,
        ]);
    }

    public function getContentText(Entity $content): string
    {
        /** @var \XF\Entity\ConversationMessage $content */
        return $content->message;
    }

    public function getContentLink(Entity $content): string
    {
        return \XF::app()->router('public')->buildLink('conversations/messages', $content);
    }

    public function getBreadcrumbs(Entity $content): array
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

    public function revertToVersion(Entity $content, EditHistory $history, EditHistory $previous = null): \XF\Entity\ConversationMessage
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $content */
        /** @var \SV\ConversationImprovements\XF\Service\Conversation\MessageEditor $editor */
        $editor = \XF::app()->service('XF:Conversation\MessageEditor', $content);

        $editor->logEdit(false);
        $editor->setMessageContent($history->old_text);

        if ($previous === null || ($previous->edit_user_id !== $content->user_id))
        {
            $content->last_edit_date = 0;
        }
        else
        {
            $content->last_edit_date = $previous->edit_date;
            $content->last_edit_user_id = $previous->edit_user_id;
        }

        $editor->save();

        return $content;
    }

    /**
     * @param string      $text
     * @param Entity|null $content
     * @return string
     */
    public function getHtmlFormattedContent($text, Entity $content = null): string
    {
        return \XF::app()->templater()->func('bb_code', [
            $text,
            'conversation_message',
            $content,
        ]);
    }

    public function getEntityWith(): array
    {
        $visitor = \XF::visitor();

        return [
            'Conversation',
            'Conversation.Users|' . $visitor->user_id,
        ];
    }
}
