<?php
namespace Dizda\BankManager\CoreBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;



/** Every time the balance is gonna change, we'll add the new current balance. (useful to make some stats.. :))
 * @MongoDB\EmbeddedDocument */
class BalanceHistory
{
    /** @MongoDB\Id(strategy="auto") */
    private $id;
    
    /** La balance au moment T
     *  @MongoDB\Float */
    private $balance;
    
    /** @MongoDB\Date */
    private $date_fetched;
    
    public function __construct()
    {
        $this->date_fetched = new \DateTime();
    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set balance
     *
     * @param float $balance
     * @return BalanceHistory
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * Get balance
     *
     * @return float $balance
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set date_fetched
     *
     * @param date $dateFetched
     * @return BalanceHistory
     */
    public function setDateFetched($dateFetched)
    {
        $this->date_fetched = $dateFetched;
        return $this;
    }

    /**
     * Get date_fetched
     *
     * @return date $dateFetched
     */
    public function getDateFetched()
    {
        return $this->date_fetched;
    }

    public function __toString()
    {
        return $this->date_fetched->format('d/m/Y') . '       ' . $this->balance;
    }
}
