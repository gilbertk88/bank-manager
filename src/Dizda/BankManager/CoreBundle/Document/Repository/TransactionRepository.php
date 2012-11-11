<?php

namespace Dizda\BankManager\CoreBundle\Document\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;


/**
 * SiteHitRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TransactionRepository extends DocumentRepository
{

    public function getMonthTransactions($account, \DateTime $date)
    {

        $dateStart = clone $date->modify('first day of this month')->setTime(0,0,0);
        $dateEnd   = $date->modify('last day of this month')->setTime(23,59,59);


        $qb = $this->createQueryBuilder('s')
                   ->field('account.$id')->equals($account)
                   ->field('date_transaction')->gte($dateStart)
                   ->field('date_transaction')->lte($dateEnd)
                   ->sort('date_transaction', 'desc');

        return $qb->getQuery()->execute();

    }


    public function compareLastMonths($account, $limitMonths = 6)
    {
        $beginMonth = new \DateTime(date('Y-m-d',(mktime(0, 0, 0, date("m"), 1, date("Y") ))));
        $beginMonth = $beginMonth->sub(new \DateInterval('P'.$limitMonths.'M'));

        $qb = $this->createQueryBuilder('s')
                    ->field('account.$id')->equals($account)
                    ->field('date_transaction')->gte($beginMonth) /* Previous month, only previous month, because transactions cannot exist after this moment oO */
                    //->field('label')->notEqual(new \MongoRegex('/^VIR/i'))
                    //->field('date')->lte(new \DateTime()) /* (this.date.getMonth() + 1) cause javascript return 0 to 11 months */
                    ->field('excluded')->notEqual(true)
                    ->map('function() {
                        emit( {month: ("0" + (this.date_transaction.getMonth() + 1)).slice(-2) + "/" + this.date_transaction.getFullYear() }, this.amount)
                    }')
                    ->reduce('function(k, v) {
                                var i, positive = 0, negative = 0, count = 0;
                                for (i in v) {
                                  var value = parseFloat(v[i]);
                                  if(value > 0)
                                  {
                                    positive += value;
                                  }else{
                                    negative += value;
                                  }
                                  count++;
                                }
                                
                                var reduced = {"positive":positive, "negative":negative, "count":count};
                                return reduced;
                            }');

        $query = $qb->getQuery()
                    ->execute()->toArray();
        
        $months         = [];
        $cpt            = 0;
        $previousMonth  = '';
        
        foreach($query as $month)
        {
            $cpt++;
            $loopMonth = $month['_id']['month']; // string(7) "08/2012"
            
            if( is_array($month['value']) )
            {
                $months[$loopMonth] = [ 'positive' => $month['value']['positive'],
                                        'negative' => $month['value']['negative'],
                                        'count'    => $month['value']['count']];
            }else{
                $value = (float) $month['value'];
                
                if( $value > 0)
                {
                    $months[$loopMonth] = [ 'positive' => $value,
                                            'negative' => 0,
                                            'count'    => 1];
                }else{
                    $months[$loopMonth] = [ 'positive' => 0,
                                            'negative' => $value,
                                            'count'    => 1];
                }
                
            }
            
            /* calcul differance betweet current month & last month */
            if( $cpt === 1 )
            {
                $months[$loopMonth]['diff_from_last_month_positive'] = 0;
                $months[$loopMonth]['diff_from_last_month_negative'] = 0;
                $months[$loopMonth]['diff_from_last_month_count']    = 0;
            }else{

                /* we calculate the diff between last month and current month "evolution rate" */
                $months = $this->diffCount($months, $loopMonth, $previousMonth);
            }
            
            $previousMonth = $loopMonth;
            
        }
        
        if(!isset($months[date('m/Y')]))
        {
            $months[date('m/Y')] = [ 'positive' => 0,
                                     'negative' => 0,
                                     'count'    => 0 ];
            
            $months = $this->diffCount($months, date('m/Y'), $previousMonth);
        }
        



        return $months;
    }
    
    
    private function diffCount($months, $loopMonth, $previousMonth)
    {
        /* we calculate the diff between last month and current month "evolution rate" */
        if( $months[$previousMonth]['positive'] == 0 ) /* avoid the division by 0 */
            $evolutionPositive  = $months[$loopMonth]['positive'];
        else
            $evolutionPositive  = (($months[$loopMonth]['positive'] - $months[$previousMonth]['positive']) / $months[$previousMonth]['positive']) * 100;

        if( $months[$previousMonth]['negative'] == 0 )
            $evolutionNegative  = $months[$loopMonth]['negative'];
        else
            $evolutionNegative  = (($months[$loopMonth]['negative'] - $months[$previousMonth]['negative']) / $months[$previousMonth]['negative']) * 100;

        if( $months[$previousMonth]['count'] == 0 )
            $evolutionCount     = $months[$loopMonth]['count'];
        else
            $evolutionCount     = (($months[$loopMonth]['count'] - $months[$previousMonth]['count']) / $months[$previousMonth]['count']) * 100;

        $evolutionPositive  = round($evolutionPositive);
        $evolutionNegative  = round($evolutionNegative);
        $evolutionCount     = round($evolutionCount);

        $months[$loopMonth]['diff_from_last_month_positive'] = ($evolutionPositive > 0) ? '+' . $evolutionPositive : $evolutionPositive;
        $months[$loopMonth]['diff_from_last_month_negative'] = ($evolutionNegative > 0) ? '+' . $evolutionNegative : $evolutionNegative;
        $months[$loopMonth]['diff_from_last_month_count']    = ($evolutionCount > 0) ? '+' . $evolutionCount : $evolutionCount;
        
        return $months;
    }
 
    
    
    
}