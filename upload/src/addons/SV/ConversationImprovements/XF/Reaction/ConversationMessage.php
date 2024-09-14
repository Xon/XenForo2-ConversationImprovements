<?php

namespace SV\ConversationImprovements\XF\Reaction;

use XF\Mvc\Entity\Entity;

/**
 * @extends \XF\Reaction\ConversationMessage
 */
class ConversationMessage extends XFCP_ConversationMessage
{
    public function reactionsCounted(Entity $entity)
    {
        return \XF::options()->svCountConversationLikes ?? true;
    }
}