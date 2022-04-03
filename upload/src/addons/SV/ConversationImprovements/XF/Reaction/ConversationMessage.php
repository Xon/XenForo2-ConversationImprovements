<?php

namespace SV\ConversationImprovements\XF\Reaction;

use XF\Mvc\Entity\Entity;

/**
 * Extends \XF\Reaction\ConversationMessage
 */
class ConversationMessage extends XFCP_ConversationMessage
{
    /** @noinspection PhpMissingReturnTypeInspection */
    public function reactionsCounted(Entity $entity)
    {
        return \XF::options()->svCountConversationLikes;
    }
}