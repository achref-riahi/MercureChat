<?php
namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *  mercure=true,
 *  normalizationContext={"groups"={"read"}},
 *  denormalizationContext={"groups"={"write"}},
 *  itemOperations={"get"},
 *  collectionOperations={"post","get"})
 * @ORM\Entity
 */
class Message
{
    /**
     * @var int The entity Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string the text
     *
     * @ORM\Column
     * @Assert\NotBlank
     * @Groups({"read", "write"})
     */
    public $text = '';

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @Groups({"read"})
     */
    public $author;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read"})
     */
    public $sentAt;

    public function __construct()
    {
      $this->sentAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setAuthor($user){
      $this->author = $user;
      return $this;
    }
}
