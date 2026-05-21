<?php

namespace App\Repository;

use App\Entity\Commission;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commission>
 */
class CommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commission::class);
    }

    /**
     * @return Commission[]
     */
    public function findForBrowse(?string $q = null, ?int $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->leftJoin('c.artist', 'a')->addSelect('a')
            ->orderBy('c.createdAt', 'DESC');

        if ($categoryId > 0) {
            $qb->andWhere('cat.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($q !== null && $q !== '') {
            $qb->andWhere('c.title LIKE :q OR c.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Commission[]
     */
    public function findForClientProgress(User $client): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->leftJoin('c.artist', 'a')->addSelect('a')
            ->andWhere('c.client = :client')
            ->setParameter('client', $client)
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Commission[]
     */
    public function findForArtist(User $artist, int $limit = 6): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')->addSelect('client')
            ->andWhere('c.artist = :artist')
            ->setParameter('artist', $artist)
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Requested slots are commissions with a client assigned but not yet accepted by the artist/staff.
     *
     * @return Commission[]
     */
    public function findPendingRequests(?User $artist = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')->addSelect('client')
            ->leftJoin('c.artist', 'artist')->addSelect('artist')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->andWhere('c.client IS NOT NULL')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'Pending')
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC');

        if ($artist !== null) {
            $qb->andWhere('c.artist = :artist')
                ->setParameter('artist', $artist);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Accepted client commissions that staff/artists still need to finish.
     *
     * @return Commission[]
     */
    public function findActiveClientCommissions(?User $artist = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')->addSelect('client')
            ->leftJoin('c.artist', 'artist')->addSelect('artist')
            ->leftJoin('c.category', 'cat')->addSelect('cat')
            ->andWhere('c.client IS NOT NULL')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'In Progress')
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC');

        if ($artist !== null) {
            $qb->andWhere('c.artist = :artist')
                ->setParameter('artist', $artist);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Commission[] Returns an array of Commission objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commission
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
