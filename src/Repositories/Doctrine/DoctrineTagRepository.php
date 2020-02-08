<?php declare(strict_types=1);

namespace Sihae\Repositories\Doctrine;

use Sihae\Entities\Tag;
use Doctrine\ORM\Query;
use Sihae\Entities\Post;
use Doctrine\ORM\EntityManager;
use Sihae\Repositories\TagRepository;
use Doctrine\Common\Persistence\ObjectRepository;

class DoctrineTagRepository implements TagRepository
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ObjectRepository<Tag>
     */
    private $repository;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Tag::class);
    }

    /**
     * @param string $slug
     * @return Tag|null
     */
    public function findBySlug(string $slug) : ?Tag
    {
        return $this->repository->findOneBy(['slug' => $slug]);
    }

    /**
     * @return array<Tag>
     */
    public function findAllOrderedByUsage() : array
    {
        $dql =
            'SELECT t, p, (
                 SELECT COUNT(p2)
                 FROM Sihae\Entities\Post p2
                 WHERE p2 MEMBER OF t.posts
             ) AS HIDDEN post_count
             FROM Sihae\Entities\Tag t
             LEFT JOIN t.posts p
             ORDER BY post_count DESC';

        return $this->entityManager->createQuery($dql)->getResult();
    }

    /**
     * @todo rename this to something clearer
     * @param array<int> $existingTags
     * @param array<string> $newTags
     * @return array<Tag>
     */
    public function findAll(array $existingTags, array $newTags) : array
    {
        return array_merge(
            $this->find($existingTags),
            $this->findOrCreate($newTags)
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function findAllAsArray() : array
    {
        $query = $this->entityManager->createQuery(
            'SELECT partial t.{id, name}
             FROM Sihae\Entities\Tag t'
        );

        return $query->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * @param Post $post
     * @return array<string, int|string>
     */
    public function findAllForPostAsArray(Post $post) : array
    {
        $query = $this->entityManager->createQuery(
            'SELECT partial t.{id, name}
             FROM Sihae\Entities\Tag t
             JOIN t.posts p
             WHERE :post MEMBER OF t.posts
             GROUP BY t.id'
        );

        $query->setParameter('post', $post);

        return $query->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Find tags from the given list of IDs
     *
     * @param array<int> $tagIds
     * @return array<Tag>
     */
    private function find(array $tagIds) : array
    {
        $query = $this->entityManager->createQuery(
            'SELECT t
             FROM Sihae\Entities\Tag t
             WHERE t.id IN (:tagIds)'
        );

        $query->setParameter(':tagIds', $tagIds);

        return $query->getResult();
    }

    /**
     * Find or create tags from the given list of names
     *
     * @param array<string> $tagNames
     * @return array<Tag>
     */
    private function findOrCreate(array $tagNames) : array
    {
        return array_map(function (string $name) : Tag {
            // check for an existing tag with this name first
            $tag = $this->repository->findOneBy(['name' => $name]);

            if ($tag === null) {
                $tag = new Tag($name);

                $this->entityManager->persist($tag);
            }

            return $tag;
        }, $tagNames);
    }
}
