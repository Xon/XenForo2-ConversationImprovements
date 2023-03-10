<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\Search\Data;

use SV\SearchImprovements\Search\DiscussionTrait;
use XF\Mvc\Entity\Entity;
use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;
use XF\Search\Query\MetadataConstraint;
use function assert;
use function count;
use function implode;
use function is_callable;
use function is_string;

/**
 * A search handler for conversation messages.
 */
class ConversationMessage extends AbstractData
{
    protected static $svDiscussionEntity = \XF\Entity\ConversationMaster::class;
    use DiscussionTrait;
    /**
     * @param Entity $entity
     * @return IndexRecord|null
     */
    public function getIndexData(Entity $entity)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $entity */
        if (!$entity->Conversation)
        {
            return null;
        }

        $conversation = $entity->Conversation;
        if ($entity->isFirstMessage())
        {
            return $this->searcher->handler('conversation')->getIndexData($conversation);
        }

        return IndexRecord::create('conversation_message', $entity->message_id, [
            'message'       => $entity->message_,
            'date'          => $entity->message_date,
            'user_id'       => $entity->user_id,
            'discussion_id' => $entity->conversation_id,
            'metadata'      => $this->getMetadata($entity),
        ]);
    }

    /**
     * @param \XF\Entity\ConversationMessage $entity
     * @return array
     */
    protected function getMetadata(\XF\Entity\ConversationMessage $entity)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $entity */
        $conversation = $entity->Conversation;

        $metaData = [
            'conversation' => $conversation->conversation_id,
            'recipients'   => $conversation->getSearchableRecipients(),
        ];

        $this->populateDiscussionMetaData($conversation, $metaData);

        return $metaData;
    }

    /**
     * @param MetadataStructure $structure
     */
    public function setupMetadataStructure(MetadataStructure $structure)
    {
        $structure->addField('conversation', MetadataStructure::INT);

        $this->setupDiscussionMetadataStructure($structure);
    }

    /**
     * @param Entity $entity
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
     * @return array
     */
    public function getTemplateData(Entity $entity, array $options = [])
    {
        return [
            'message' => $entity,
            'options' => $options,
        ];
    }

    /**
     * @param bool $forView
     * @return array
     */
    public function getEntityWith($forView = false)
    {
        $with = ['Conversation'];

        if ($forView)
        {
            $with[] = 'User';

            $visitor = \XF::visitor();
            $with[] = 'Conversation.Users|' . $visitor->user_id;
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
        if (!$visitor->user_id)
        {
            return null;
        }

        return [
            'title' => \XF::phrase('sv_convimprov_search_conversations'),
            'order' => 1010,
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
    public function applyTypeConstraintsFromInput(\XF\Search\Query\Query $query, \XF\Http\Request $request, array &$urlConstraints)
    {
        $recipients = $request->filter('c.recipients', 'str', '');
        assert(is_string($recipients));
        if ($recipients !== '')
        {
            $recipients = \XF\Util\Arr::stringToArray($recipients, '/,\s*/');
            if (count($recipients) !== 0)
            {
                /** @var \XF\Repository\User $userRepo */
                $userRepo = \XF::repository('XF:User');
                $matchedUsers = $userRepo->getUsersByNames($recipients, $notFound);
                if ($notFound)
                {
                    $query->error('recipients', \XF::phrase(
                        'following_members_not_found_x',
                        ['members' => implode(', ', $notFound)]
                    ));
                }
                else
                {
                    $query->withMetadata('recipients', $matchedUsers->keys(), 'all');
                    $urlConstraints['recipients'] = implode(', ', $recipients);
                }
            }
        }

        $minReplyCount = (int)$request->filter('c.min_reply_count', 'uint', 0);
        if ($minReplyCount !== 0)
        {
            $query->withSql(new \XF\Search\Query\SqlConstraint(
                'conversation.reply_count >= %s',
                $minReplyCount,
                $this->getConversationQueryTableReference()
            ));
        }
        else
        {
            unset($urlConstraints['min_reply_count']);
        }

        $conversationId = (int)$request->filter('c.conversation', 'uint', 0);
        if ($conversationId !== 0)
        {
            $query->withMetadata('conversation', $conversationId);
            if (is_callable([$query, 'inTitleOnly']))
            {
                $query->inTitleOnly(false);
            }
        }
        else
        {
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
     * @param string|mixed $order
     * @return \XF\Search\Query\SqlOrder|null
     */
    public function getTypeOrder($order)
    {
        if ($order === 'replies')
        {
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
     * @return array
     */
    public function getTypePermissionConstraints(\XF\Search\Query\Query $query, $isOnlyType)
    {
        // $isOnlyType is false when searching both conversation types
        $queryTypes = $query->getTypes();
        $types = $this->getSearchableContentTypes();
        if ($isOnlyType || ($queryTypes && !\array_diff($queryTypes, $types)))
        {
            // todo this isn't particularly efficient with MySQL backend
            $recipientConstraint = new MetadataConstraint('discussion_user', \XF::visitor()->user_id);

            return [$recipientConstraint];
        }

        return [];
    }

    /**
     * @param Entity $entity
     * @param array  $resultIds
     * @return bool
     */
    public function canIncludeInResults(Entity $entity, array $resultIds)
    {
        /** @var \SV\ConversationImprovements\XF\Entity\ConversationMessage $entity */
        $conversationId = $entity->conversation_id;
        $conversationKey = 'conversation-'.$conversationId;
        if (isset($resultIds[$conversationKey]) && $entity->isFirstMessage())
        {
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
