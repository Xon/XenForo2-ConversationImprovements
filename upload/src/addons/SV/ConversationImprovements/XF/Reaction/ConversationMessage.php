<?php

namespace SV\ConversationImprovements\XF\Reaction;

use XF\Mvc\Entity\Entity;

/**
 * @extends \XF\Reaction\ConversationMessage
 */
class ConversationMessage extends XFCP_ConversationMessage
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function reactionsCounted(Entity $entity)
    {
        return (bool)(\XF::options()->svCountConversationLikes ?? true);
    }
}