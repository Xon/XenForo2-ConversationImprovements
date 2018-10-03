<?php

/*
 * This file is part of a XenForo add-on.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SV\ConversationImprovements\Search\Data;

use XF\Mvc\Entity\Entity;
use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;
use XF\Search\Query\MetadataConstraint;

/**
 * A search handler for conversation messages.
 */
class ConversationMessage extends AbstractData
{
    /**
     * @param Entity $entity
     *
     * @return IndexRecord|null
     */
    public function getIndexData(Entity $entity)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $entity */
        if (!$entity->Conversation) {
            return null;
        }

        $conversation = $entity->Conversation;
        if ($entity->isFirstMessage()) {
            return $this->searcher->handler('conversation')->getIndexData(
                $conversation
            );
        }

        return IndexRecord::create('conversation_message', $entity->message_id, [
            'message'       => $entity->message_,
            'date'          => $entity->message_date,
            'user_id'       => $entity->user_id,
            'discussion_id' => $entity->conversation_id,
            'metadata'      => $this->getMetadata($entity)
        ]);
    }

    /**
     * @param \XF\Entity\ConversationMessage $entity
     * @return array
     */
    protected function getMetadata(\XF\Entity\ConversationMessage $entity)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMaster $conversation */
        $conversation = $entity->Conversation;
        $recipients = \array_keys($conversation->recipients);
        $recipients[] = $conversation->user_id;
        return [
            'conversation' => $entity->conversation_id,
            'recipients'   => \array_unique($recipients),
        ];
    }

    /**
     * @param MetadataStructure $structure
     */
    public function setupMetadataStructure(MetadataStructure $structure)
    {
        $structure->addField('conversation', MetadataStructure::INT);
        $structure->addField('recipients', MetadataStructure::INT);
    }

    /**
     * @param Entity $entity
     *
     * @return int
     */
    public function getResultDate(Entity $entity)
    {
        /** @var \XF\Entity\ConversationMessage $entity */
        return $entity->message_date;
    }

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @return array
     */
    public function getTemplateData(Entity $entity, array $options = [])
    {
        return [
            'message' => $entity,
            'options' => $options
        ];
    }

    /**
     * @param bool $forView
     *
     * @return array
     */
    public function getEntityWith($forView = false)
    {
        $with = ['Conversation'];

        if ($forView) {
            $with[] = 'User';

            $visitor = \XF::visitor();
            $with[] = "Conversation.Users|{$visitor->user_id}";
        }

        return $with;
    }

    /**
     * @return string
     */
    public function getTemplateName()
    {
        return 'public:sv_convimprov_search_result_conversation_message';
    }

    /**
     * @return array
     */
    public function getSearchableContentTypes()
    {
        return ['conversation_message', 'conversation'];
    }

    /**
     * @return array|null
     */
    public function getSearchFormTab()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id) {
            return null;
        }

        return [
            'title' => \XF::phrase('sv_convimprov_search_conversations'),
            'order' => 1010
        ];
    }

    /**
     * @return string
     */
    public function getTypeFormTemplate()
    {
        return 'public:sv_convimprov_search_form_conversation_message';
    }

    /**
     * @param \XF\Search\Query\Query $query
     * @param \XF\Http\Request       $request
     * @param array                  $urlConstraints
     */
    public function applyTypeConstraintsFromInput(
        \XF\Search\Query\Query $query,
        \XF\Http\Request $request,
        array &$urlConstraints
    ) {
        $recipients = $request->filter('c.recipients', 'str');
        if ($recipients) {
            $recipients = preg_split(
                '/,\s*/',
                $recipients,
                -1,
                PREG_SPLIT_NO_EMPTY
            );
            if ($recipients) {
                /** @var \XF\Repository\User $userRepo */
                $userRepo = \XF::repository('XF:User');
                $matchedUsers = $userRepo->getUsersByNames(
                    $recipients,
                    $notFound
                );
                if ($notFound) {
                    $query->error('recipients', \XF::phrase(
                        'following_members_not_found_x',
                        ['members' => implode(', ', $notFound)]
                    ));
                } else {
                    $query->withMetadata(
                        'recipients',
                        $matchedUsers->keys(),
                        'all'
                    );
                    $urlConstraints['recipients'] = implode(', ', $recipients);
                }
            }
        }

        $minReplyCount = $request->filter('c.min_reply_count', 'uint');
        if ($minReplyCount) {
            $query->withSql(new \XF\Search\Query\SqlConstraint(
                'conversation.reply_count >= %s',
                $minReplyCount,
                $this->getConversationQueryTableReference()
            ));
        } else {
            unset($urlConstraints['min_reply_count']);
        }

        $conversationId = $request->filter('c.conversation', 'uint');
        if ($conversationId) {
            $query->withMetadata('conversation', $conversationId);
            $query->inTitleOnly(false);
        } else {
            unset($urlConstraints['conversation']);
        }
    }

    /**
     * @return string
     */
    public function getGroupByType()
    {
        return 'conversation';
    }

    /**
     * @return \XF\Search\Query\SqlOrder|null
     */
    public function getTypeOrder($order)
    {
        if ($order == 'replies') {
            return new \XF\Search\Query\SqlOrder(
                'conversation.reply_count DESC',
                $this->getConversationQueryTableReference()
            );
        }

        return null;
    }

    /**
     * @param \XF\Search\Query\Query $query
     * @param bool                   $isOnlyType
     *
     * @return array
     */
    public function getTypePermissionConstraints(
        \XF\Search\Query\Query $query,
        $isOnlyType
    ) {
        // $isOnlyType is false when searching both conversation types
        $queryTypes = $query->getTypes();
        $types = $this->getSearchableContentTypes();
        if ($isOnlyType || ($queryTypes && !array_diff($queryTypes, $types))) {
            $recipientConstraint = new MetadataConstraint(
                'recipients',
                \XF::visitor()->user_id
            );
            return [$recipientConstraint];
        }

        return [];
    }

    /**
     * @param Entity $entity
     * @param array $resultIds
     *
     * @return bool
     */
    public function canIncludeInResults(Entity $entity, array $resultIds)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $entity */
        $conversationId = $entity->conversation_id;
        $conversationKey = "conversation-{$conversationId}";
        if (isset($resultIds[$conversationKey]) && $entity->isFirstMessage()) {
            return false;
        }

        return true;
    }

    /**
     * @return \XF\Search\Query\TableReference
     */
    protected function getConversationQueryTableReference()
    {
        return new \XF\Search\Query\TableReference(
            'conversation',
            'xf_conversation_master',
            'conversation.conversation_id = search_index.discussion_id'
        );
    }
}
