<?php

namespace App\Document;

use App\Repository\CustomerRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ODM\Document()]
class Customer
{
    #[ODM\Id(strategy: 'UUID')]
    private ?string $id = null;


    public function __construct(
        #[ODM\Field(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
         private ?string $fullName,

        #[ODM\Field(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length( max: 255)]
        #[Assert\Email]
        #[ODM\Index(unique: true, order: 'asc')]
         private ?string $email = null,

        #[ODM\Field(type: 'string')]
        #[Assert\NotBlank]
        #[Assert\Length( max: 255)]
         private ?string $city = null
    )
    {

    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->fullName,
            'email' => $this->email,
            'city' => $this->city
        ];
    }



}
