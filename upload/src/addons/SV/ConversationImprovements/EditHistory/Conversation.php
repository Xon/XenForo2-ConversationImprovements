<?php
/**
 * @noinspection PhpMissingParentCallCommonInspection
 */

namespace SV\ConversationImprovements\EditHistory;

use SV\ConversationImprovements\XF\Entity\ConversationMaster as ExtendedConversationMasterEntity;
use SV\ConversationImprovements\XF\Service\Conversation\Editor as ExtendedEditorService;
use SV\StandardLib\Helper;
use XF\EditHistory\AbstractHandler;
use XF\Entity\ConversationMaster as ConversationMasterEntity;
use XF\Entity\EditHistory as EditHistoryEntity;
use XF\Mvc\Entity\Entity;
use XF\Phrase;
use XF\Service\Conversation\Editor as EditorService;

/**
 * An edit history handler for conversations.
 */
class Conversation extends AbstractHandler
{
    public function canViewHistory(Entity $content): bool
    {
        /** @var ExtendedConversationMasterEntity $content */
        return $content->canView() && $content->canViewHistory();
    }

    public function canRevertContent(Entity $content): bool
    {
        /** @var ConversationMasterEntity $content */
        return $content->canEdit();
    }

    public function getContentTitle(Entity $content): Phrase
    {
        return \XF::phrase(\XF::$versionId >= 2030000 ? 'direct_message_title' : 'conversation_title');
    }

    public function getContentText(Entity $content): string
    {
        /** @var ConversationMasterEntity $content */
        return $content->title;
    }

    public function getContentLink(Entity $content): string
    {
        return \XF::app()->router('public')->buildLink('conversations', $content);
    }

    public function getBreadcrumbs(Entity $content): array
    {
        $router = \XF::app()->router('public');

        $breadcrumbs[] = [
            'value' => \XF::phrase(\XF::$versionId >= 2030000 ? 'direct_messages' : 'conversations'),
            'href'  => $router->buildLink('conversations'),
        ];

        /** @var ConversationMasterEntity $content */
        $breadcrumbs[] = [
            'value' => $content->title,
            'href'  => $router->buildLink('conversations', $content),
        ];

        return $breadcrumbs;
    }

    public function revertToVersion(Entity $content, EditHistoryEntity $history, ?EditHistoryEntity $previous = null): ConversationMasterEntity
    {
        /** @var ExtendedConversationMasterEntity $content */
        /** @var ExtendedEditorService $editor */
        $editor = Helper::service(EditorService::class, $content);

        $editor->logEdit(false);
        $editor->setTitle($history->old_text);

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
    public function getHtmlFormattedContent($text, ?Entity $content = null): string
    {
        return \XF::escapeString($text);
    }

    public function getEntityWith(): array
    {
        $visitor = \XF::visitor();

        return [
            'Users|' . $visitor->user_id,
        ];
    }
}
