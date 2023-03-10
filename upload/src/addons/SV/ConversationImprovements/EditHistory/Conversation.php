<?php

namespace SV\ConversationImprovements\EditHistory;

use XF\EditHistory\AbstractHandler;
use XF\Entity\ConversationMaster;
use XF\Entity\EditHistory;
use XF\Mvc\Entity\Entity;

/**
 * An edit history handler for conversations.
 */
class Conversation extends AbstractHandler
{
    public function canViewHistory(Entity $content): bool
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $content */
        return $content->canView() && $content->canViewHistory();
    }

    public function canRevertContent(Entity $content): bool
    {
        /** @var \XF\Entity\ConversationMaster $content */
        return $content->canEdit();
    }

    public function getContentTitle(Entity $content): \XF\Phrase
    {
        return \XF::phrase('conversation_title');
    }

    public function getContentText(Entity $content): string
    {
        /** @var \XF\Entity\ConversationMaster $content */
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
            'value' => \XF::phrase('conversations'),
            'href'  => $router->buildLink('conversations'),
        ];

        /** @var \XF\Entity\ConversationMaster $content */
        $breadcrumbs[] = [
            'value' => $content->title,
            'href'  => $router->buildLink('conversations', $content),
        ];

        return $breadcrumbs;
    }

    public function revertToVersion(Entity $content, EditHistory $history, EditHistory $previous = null): ConversationMaster
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $content */
        /** @var \SV\ConversationImprovements\XF\Service\Conversation\Editor $editor */
        $editor = \XF::app()->service('XF:Conversation\Editor', $content);

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
    public function getHtmlFormattedContent($text, Entity $content = null): string
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
