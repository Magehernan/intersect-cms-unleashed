<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CmsShopHistory
 *
 * @ORM\Table(name="cms_shop_history")
 * @ORM\Entity
 */
class CmsShopHistory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var int
     *
     * @ORM\Column(name="shop_id", type="integer", nullable=false)
     */
    private $shopId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="text", length=65535, nullable=false)
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="credits_now", type="integer", nullable=false)
     */
    private $creditsNow;



    /**
     * Get the value of id
     *
     * @return  int
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @param  int  $id
     *
     * @return  self
     */ 
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of date
     *
     * @return  \DateTime
     */ 
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of date
     *
     * @param  \DateTime  $date
     *
     * @return  self
     */ 
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of shopId
     *
     * @return  int
     */ 
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Set the value of shopId
     *
     * @param  int  $shopId
     *
     * @return  self
     */ 
    public function setShopId(int $shopId)
    {
        $this->shopId = $shopId;

        return $this;
    }

    /**
     * Get the value of userId
     *
     * @return  string
     */ 
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the value of userId
     *
     * @param  string  $userId
     *
     * @return  self
     */ 
    public function setUserId(string $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the value of creditsNow
     *
     * @return  int
     */ 
    public function getCreditsNow()
    {
        return $this->creditsNow;
    }

    /**
     * Set the value of creditsNow
     *
     * @param  int  $creditsNow
     *
     * @return  self
     */ 
    public function setCreditsNow(int $creditsNow)
    {
        $this->creditsNow = $creditsNow;

        return $this;
    }
}
