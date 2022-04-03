<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\EditHistory;

use XF\EditHistory\AbstractHandler;
use XF\Entity\EditHistory;
use XF\Mvc\Entity\Entity;

/**
 * An edit history handler for conversations.
 */
class Conversation extends AbstractHandler
{
    /**
     * @param Entity $content
     * @return bool
     */
    public function canViewHistory(Entity $content)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $content */
        return ($content->canView() && $content->canViewHistory());
    }

    /**
     * @param Entity $content
     * @return bool
     */
    public function canRevertContent(Entity $content)
    {
        /** @var \XF\Entity\ConversationMaster $content */
        return $content->canEdit();
    }

    /**
     * @param Entity $content
     * @return \XF\Phrase
     */
    public function getContentTitle(Entity $content)
    {
        return \XF::phrase('conversation_title');
    }

    /**
     * @param Entity $content
     * @return string
     */
    public function getContentText(Entity $content)
    {
        /** @var \XF\Entity\ConversationMaster $content */
        return $content->title;
    }

    /**
     * @param Entity $content
     * @return string
     */
    public function getContentLink(Entity $content)
    {
        return \XF::app()->router('public')->buildLink('conversations', $content);
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

        /** @var \XF\Entity\ConversationMaster $content */
        $breadcrumbs[] = [
            'value' => $content->title,
            'href'  => $router->buildLink('conversations', $content),
        ];

        return $breadcrumbs;
    }

    /**
     * @param Entity      $content
     * @param EditHistory $history
     * @param EditHistory $previous
     * @return \XF\Entity\ConversationMaster
     */
    public function revertToVersion(Entity $content, EditHistory $history, EditHistory $previous = null)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $content */
        /** @var \SV\ConversationImprovements\XF\Service\Conversation\Editor $editor */
        $editor = \XF::app()->service('XF:Conversation\Editor', $content);

        $editor->logEdit(false);
        $editor->setTitle($history->old_text);

        if (!$previous || ($previous->edit_user_id !== $content->user_id))
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
     * @param string $text
     * @param Entity $content
     * @return string
     */
    public function getHtmlFormattedContent($text, Entity $content = null)
    {
        return $text;
    }

    /**
     * @return array
     */
    public function getEntityWith()
    {
        $visitor = \XF::visitor();

        return [
            'Users|' . $visitor->user_id,
        ];
    }
}
