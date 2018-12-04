<?php

namespace SV\ConversationImprovements\XF\Like;

use XF\Mvc\Entity\Entity;

/**
 * Extends \XF\Like\ConversationMessage
 */
class ConversationMessage extends XFCP_ConversationMessage
{
    public function likesCounted(/** @noinspection PhpUnusedParameterInspection */ Entity $entity)
    {
        return \XF::options()->svCountConversationLikes;
    }
}