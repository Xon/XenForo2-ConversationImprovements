<?php
/**
 * @noinspection PhpMissingParentCallCommonInspection
 */

namespace SV\ConversationImprovements\EditHistory;

use SV\ConversationImprovements\XF\Entity\ConversationMessage as ExtendedConversationMessageEntity;
use SV\ConversationImprovements\XF\Service\Conversation\MessageEditor as ExtendedMessageEditorService;
use SV\StandardLib\Helper;
use XF\EditHistory\AbstractHandler;
use XF\Entity\ConversationMessage as ConversationMessageEntity;
use XF\Entity\EditHistory as EditHistoryEntity;
use XF\Mvc\Entity\Entity;
use XF\Phrase;
use XF\Service\Conversation\MessageEditor as MessageEditorService;

/**
 * An edit history handler for conversation messages.
 */
class ConversationMessage extends AbstractHandler
{
    public function canViewHistory(Entity $content): bool
    {
        /** @var ExtendedConversationMessageEntity $content */
        return $content->canView() && $content->canViewHistory();
    }

    public function canRevertContent(Entity $content): bool
    {
        /** @var ConversationMessageEntity $content */
        return $content->canEdit();
    }

    public function getContentTitle(Entity $content): Phrase
    {
        /** @var ConversationMessageEntity $content */
        return \XF::phrase(\XF::$versionId > 2030000 ? 'direct_message_reply_in_x' : 'conversation_message_in_x', [
            'title' => $content->Conversation->title,
        ]);
    }

    public function getContentText(Entity $content): string
    {
        /** @var ConversationMessageEntity $content */
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
            'value' => \XF::phrase(\XF::$versionId > 2030000 ? 'direct_messages' : 'conversations'),
            'href'  => $router->buildLink('conversations'),
        ];

        /** @var ConversationMessageEntity $content */
        $breadcrumbs[] = [
            'value' => $content->Conversation->title,
            'href'  => $router->buildLink('conversations/messages', $content),
        ];

        return $breadcrumbs;
    }

    public function revertToVersion(Entity $content, EditHistoryEntity $history, EditHistoryEntity $previous = null): ConversationMessageEntity
    {
        /** @var ExtendedConversationMessageEntity $content */
        /** @var ExtendedMessageEditorService $editor */
        $editor = Helper::service(MessageEditorService::class, $content);

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
