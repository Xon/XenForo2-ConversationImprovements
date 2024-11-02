<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace SV\ConversationImprovements\XF\Search\Data;

use SV\ElasticSearchEssentials\XF\Repository\ImpossibleSearchResultsException;
use SV\SearchImprovements\Repository\Search as SearchRepo;
use SV\SearchImprovements\Search\DiscussionTrait;
use SV\SearchImprovements\XF\Search\Query\Constraints\AndConstraint;
use SV\SearchImprovements\XF\Search\Query\Constraints\NotConstraint;
use SV\SearchImprovements\XF\Search\Query\Constraints\PermissionConstraint;
use SV\SearchImprovements\XF\Search\Query\Constraints\TypeConstraint;
use XF\Entity\ConversationMaster as ConversationMasterEntity;
use XF\Search\MetadataStructure;
use XF\Search\Query\MetadataConstraint;
use XF\Search\Query\Query;
use XF\Search\Query\SqlOrder;

/**
 * XF2.3+ support
 * @extends \XF\Search\Data\ConversationMessage
 */
class ConversationMessage extends XFCP_ConversationMessage
{
    protected static $svDiscussionEntity = ConversationMasterEntity::class;
    use DiscussionTrait
    {
        getTypeOrder as protected getTypeOrderTrait;
    }

    protected function getMetaData(\XF\Entity\ConversationMessage $entity): array
    {
        $metaData = parent::getMetaData($entity);

        $this->populateDiscussionMetaData($entity, $metaData);

        return $metaData;
    }

    public function setupMetadataStructure(MetadataStructure $structure): void
    {
        parent::setupMetadataStructure($structure);

        $this->setupDiscussionMetadataStructure($structure);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getTypeOrder($order): ?SqlOrder
    {
        return $this->getTypeOrderTrait($order);
    }

    /**
     * @return string
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getTemplateName()
    {
        return 'public:sv_convimprov_search_result_conversation_message';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function getTypePermissionConstraints(Query $query, $isOnlyType): array
    {
        $userId = (int)\XF::visitor()->user_id;
        $repo = SearchRepo::get();
        if (!$repo->isUsingElasticSearch())
        {
            return $isOnlyType
                // discussion_user is only populated when XFES is enabled, this likely should change
                ? [new MetadataConstraint('recipients', $userId)]
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

        $viewConstraint = new MetadataConstraint('discussion_user', $userId);
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
}
