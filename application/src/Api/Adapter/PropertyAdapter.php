<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Vocabulary;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class PropertyAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'local_name' => 'localName',
        'label' => 'label',
        'comment' => 'comment',
    ];

    public function getResourceName()
    {
        return 'properties';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\PropertyRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Omeka\Entity\Property::class;
    }

    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('item_count' == $query['sort_by']) {
                $valuesAlias = $this->createAlias();
                $resourceAlias = $this->createAlias();
                $countAlias = $this->createAlias();
                $qb->addSelect("COUNT($valuesAlias.id) HIDDEN $countAlias")
                    ->leftJoin("omeka_root.values", $valuesAlias)
                    ->leftJoin(
                        "$valuesAlias.resource", $resourceAlias,
                        'WITH', "$resourceAlias INSTANCE OF Omeka\Entity\Item"
                    )->addGroupBy("omeka_root.id")
                    ->addOrderBy($countAlias, $query['sort_order']);
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        $this->hydrateOwner($request, $entity);

        if ($this->shouldHydrate($request, 'o:local_name')) {
            $entity->setLocalName($request->getValue('o:local_name'));
        }
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }
        if ($this->shouldHydrate($request, 'o:comment')) {
            $entity->setComment($request->getValue('o:comment'));
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        $expr = $qb->expr();
        if (isset($query['owner_id']) && is_numeric($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.owner',
                $userAlias
            );
            $qb->andWhere($expr->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
        if (isset($query['vocabulary_id']) && is_numeric($query['vocabulary_id'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($expr->eq(
                "$vocabularyAlias.id",
                $this->createNamedParameter($qb, $query['vocabulary_id']))
            );
        }
        if (isset($query['vocabulary_namespace_uri'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($expr->eq(
                "$vocabularyAlias.namespaceUri",
                $this->createNamedParameter($qb, $query['vocabulary_namespace_uri']))
            );
        }
        if (isset($query['vocabulary_prefix'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($expr->eq(
                "$vocabularyAlias.prefix",
                $this->createNamedParameter($qb, $query['vocabulary_prefix']))
            );
        }

        if (isset($query['local_name'])) {
            $qb->andWhere($expr->eq(
                "omeka_root.localName",
                $this->createNamedParameter($qb, $query['local_name']))
            );
        }
        if (isset($query['term']) && $this->isTerm($query['term'])) {
            list($prefix, $localName) = explode(':', $query['term']);
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($expr->eq(
                "$vocabularyAlias.prefix",
                $this->createNamedParameter($qb, $prefix))
            );
            $qb->andWhere($expr->eq(
                "omeka_root.localName",
                $this->createNamedParameter($qb, $localName))
            );
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        // Validate local name
        if (false == $entity->getLocalName()) {
            $errorStore->addError('o:local_name', 'The local name cannot be empty.'); // @translate
        }

        // Validate label
        if (false == $entity->getLabel()) {
            $errorStore->addError('o:label', 'The label cannot be empty.'); // @translate
        }

        // Validate vocabulary
        if ($entity->getVocabulary() instanceof Vocabulary) {
            if ($entity->getVocabulary()->getId()) {
                // Vocabulary is persistent. Check for unique local name.
                $criteria = [
                    'vocabulary' => $entity->getVocabulary(),
                    'localName' => $entity->getLocalName(),
                ];
                if (!$this->isUnique($entity, $criteria)) {
                    $errorStore->addError('o:local_name', new Message(
                        'The local name "%s" is already taken.', // @translate
                        $entity->getLocalName()
                    ));
                }
            }
        } else {
            $errorStore->addError('o:vocabulary', 'A vocabulary must be set.'); // @translate
        }
    }
}
