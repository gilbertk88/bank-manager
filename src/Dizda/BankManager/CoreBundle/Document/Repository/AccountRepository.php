<?php

namespace Dizda\BankManager\CoreBundle\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;


/**
 * SiteHitRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AccountRepository extends DocumentRepository
{

    public function getMonthTransactions($account, \DateTime $date)
    {

        $dateStart = clone $date->modify('first day of this month')->setTime(0,0,0);
        $dateEnd   = $date->modify('last day of this month')->setTime(23,59,59);


        $qb = $this->createQueryBuilder('b')
            ->field('_id')->equals($account)
            //->field('balanceHistory.date_fetched')->gte($dateStart)
            ->where("function() { return this.balanceHistory.balance > 100; }")
            ->sort('balanceHistory.date_fetched', 'asc');

        return $qb->getQuery()->execute();

    }


    
    
    
}