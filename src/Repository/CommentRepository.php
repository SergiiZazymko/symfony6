<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;

    public const PAGINATOR_PER_PAGE = 2;

    /**
     * CommentRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return int
     */
    public function countOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function deleteOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->delete()->getQuery()->execute();
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    private function getOldRejectedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.state = :state_rejected or c.state = :state_spam')
            ->andWhere('c.createdAt < :date')
            ->setParameters([
                'state_rejected' => 'rejected',
                'state_spam' => 'spam',
                'date' => new \DateTimeImmutable(-self::DAYS_BEFORE_REJECTED_REMOVAL . ' days'),
            ])
        ;
    }

    /**
     * @param Conference $conference
     * @param int $offset
     * @return Paginator
     */
    public function getCommentPaginator(Conference $conference, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.conference = :conference')
            ->andWhere('c.state = :state')
            ->setParameter('conference', $conference)
            ->setParameter('state', Comment::STATE_PUBLISHED)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery()
        ;

        return new Paginator($query);
    }

    /**
     * @param Comment $entity
     * @param bool $flush
     * @return void
     */
    public function save(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param Comment $entity
     * @param bool $flush
     * @return void
     */
    public function remove(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
