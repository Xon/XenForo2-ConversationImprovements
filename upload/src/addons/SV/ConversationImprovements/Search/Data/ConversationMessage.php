<?php
/**
 * @noinspection PhpMissingParentCallCommonInspection
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\ConversationImprovements\Search\Data;

use SV\ElasticSearchEssentials\XF\Repository\ImpossibleSearchResultsException;
use SV\SearchImprovements\Repository\Search as SearchRepo;
use SV\SearchImprovements\Search\DiscussionTrait;
use SV\SearchImprovements\XF\Search\Query\Constraints\AndConstraint;
use SV\SearchImprovements\XF\Search\Query\Constraints\NotConstraint;
use SV\SearchImprovements\XF\Search\Query\Constraints\PermissionConstraint;
use SV\SearchImprovements\XF\Search\Query\Constraints\TypeConstraint;
use XF\Entity\ConversationMaster as ConversationMasterEntity;
use XF\Http\Request;
use XF\Mvc\Entity\Entity;
use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;
use XF\Search\Query\MetadataConstraint;
use XF\Search\Query\Query;
use XF\Search\Query\SqlConstraint;
use XF\Search\Query\SqlOrder;
use XF\Search\Query\TableReference;
use function is_callable;

/**
 * XF2.1/XF2.2
 * A search handler for conversation messages.
 */
class ConversationMessage extends AbstractData
{
    protected static $svDiscussionEntity = ConversationMasterEntity::class;
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

        if ($entity->isFirstMessage())
        {
            $conversation = $entity->Conversation;
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
        $recipients = $conversation->recipient_user_ids;
        $activeRecipients = $conversation->active_recipient_user_ids;

        $metaData = [
            'conversation' => $entity->conversation_id,
            'recipients'   => $recipients,
            'active_recipients' => $activeRecipients,
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
        $structure->addField('recipients', MetadataStructure::INT);
        $structure->addField('active_recipients', MetadataStructure::INT);

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
     * @param Query $query
     * @param Request       $request
     * @param array                  $urlConstraints
     */
    public function applyTypeConstraintsFromInput(Query $query, Request $request, array &$urlConstraints)
    {
        $constraints = $request->filter([
            'c.recipients' => 'str',
            'c.min_reply_count' => 'uint',
            'c.conversation' => 'uint',
        ]);

        $repo = SearchRepo::get();
        $repo->applyUserConstraint($query, $constraints, $urlConstraints,
            'c.recipients', 'recipients'
        );

        $minReplyCount = (int)$constraints['c.min_reply_count'];
        if ($minReplyCount !== 0)
        {
            $query->withSql(new SqlConstraint(
                'conversation.reply_count >= %s',
                $minReplyCount,
                $this->getConversationQueryTableReference()
            ));
        }
        else
        {
            unset($urlConstraints['min_reply_count']);
        }

        $conversationId = (int)$constraints['c.conversation'];
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
     * @return SqlOrder|null
     */
    public function getTypeOrder($order)
    {
        if ($order === 'replies')
        {
            return new SqlOrder(
                'conversation.reply_count DESC',
                $this->getConversationQueryTableReference()
            );
        }

        return null;
    }

    /**
     * @param Query $query
     * @param bool                   $isOnlyType
     * @return array
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getTypePermissionConstraints(Query $query, $isOnlyType)
    {
        $userId = (int)\XF::visitor()->user_id;
        $repo = SearchRepo::get();
        if (!$repo->isUsingElasticSearch())
        {
            return $isOnlyType
                // discussion_user is only populated when XFES is enabled, this likely should change
                ? [new MetadataConstraint('active_recipients', $userId)]
                // Search Improvements and/or/type constraints are XFES only and don't support mysql
                : [];
        }

        if ($userId === 0)
        {
            // guests can't view conversations
            if (\XF::isAddOnActive('SV/ElasticSearchEssentials'))
            {
                throw new ImpossibleSearchResultsException();
            }

            return [
                new PermissionConstraint(new TypeConstraint(...$this->getSearchableContentTypes()))
            ];
        }

        $viewConstraint = new MetadataConstraint('active_recipients', $userId);
        if ($isOnlyType)
        {
            // Note; ElasticSearchEssentials forces all getTypePermissionConstraints to have $isOnlyType=true as it knows how to compose multiple types together
            return [
                $viewConstraint
            ];
        }

        return [
            // XF constraints are AND'ed together for positive queries (ANY/ALL), and OR'ed for all negative queries (NONE).
            // PermissionConstraint forces the sub-query as a negative query instead of being part of the AND'ed positive queries
            new PermissionConstraint(
                new AndConstraint(
                    new TypeConstraint(...$this->getSearchableContentTypes()),
                    new NotConstraint($viewConstraint)
                )
            )
        ];
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
     * @return TableReference
     */
    protected function getConversationQueryTableReference()
    {
        return new TableReference(
            'conversation',
            'xf_conversation_master',
            'conversation.conversation_id = search_index.discussion_id'
        );
    }
}
