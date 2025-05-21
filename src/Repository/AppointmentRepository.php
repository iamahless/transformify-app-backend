<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findOverlappingAppointments(
        Participant $participant,
        string $startTime,
        string $endTime,
    ): array {
        $startTime = new \DateTime($startTime);
        $endTime = new \DateTime($endTime);

        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.participants', 'p')
            ->where('p.id = :participantId')
            ->andWhere('a.startAt < :newEndTime')
            ->andWhere('a.endAt > :newStartTime')
            ->setParameter('participantId', $participant->getId())
            ->setParameter('newStartTime', $startTime)
            ->setParameter('newEndTime', $endTime);

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Appointment[] Returns an array of Appointment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Appointment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
