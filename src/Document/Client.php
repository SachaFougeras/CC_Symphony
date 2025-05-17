<?php
namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['telephone'], message: 'Ce numéro de téléphone est déjà utilisé.')]
class Client implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\Field(type: "string")]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $nom = null;

    #[MongoDB\Field(type: "string")]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email n\'est pas valide.')]
    private ?string $email = null;

    #[MongoDB\Field(type: "string")]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    private ?string $telephone = null;

    #[MongoDB\Field(type: "string")]
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    private ?string $password = null;

    #[MongoDB\Field(type: "collection")]
    private array $roles = [];
    #[MongoDB\Field(type: "string", nullable: true)]
    private ?string $securityQuestion = null;

    #[MongoDB\Field(type: "string", nullable: true)]
    private ?string $securityAnswer = null;
    #[MongoDB\Field(type: "string", nullable: true)]
    private ?string $resetCode = null;

#[MongoDB\ReferenceOne(targetDocument: Client::class)]

private ?Client $client = null;
#[MongoDB\Field(type: "int", nullable: true)]
private ?int $autoIncrementId = null;
#[MongoDB\Field(type: "int", nullable: true)]
private ?int $pinCode = null;

public function getPinCode(): ?int
{
    return $this->pinCode;
}

public function setPinCode(?int $pinCode): self
{
    $this->pinCode = $pinCode;
    return $this;
}
public function getAutoIncrementId(): ?int
{
    return $this->autoIncrementId;
}

public function setAutoIncrementId(int $autoIncrementId): self
{
    $this->autoIncrementId = $autoIncrementId;
    return $this;
}
public function getClient(): ?Client
{
    return $this->client;
}

public function setClient(?Client $client): self
{
    $this->client = $client;
    return $this;
}
    public function getResetCode(): ?string
    {
        return $this->resetCode;
    }
    
    public function setResetCode(?string $resetCode): self
    {
        $this->resetCode = $resetCode;
        return $this;
    }
    public function getSecurityQuestion(): ?string
    {
        return $this->securityQuestion;
    }

    public function setSecurityQuestion(?string $securityQuestion): self
    {
        $this->securityQuestion = $securityQuestion;
        return $this;
    }

    public function getSecurityAnswer(): ?string
    {
        return $this->securityAnswer;
    }

    public function setSecurityAnswer(?string $securityAnswer): self
    {
        $this->securityAnswer = $securityAnswer;
        return $this;
    }
    private ?string $resetToken = null;

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }
    public function getId(): ?string
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }


    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires, nettoyez-les ici
    }

    public function getUserIdentifier(): string
    {
        return $this->email; // Utilisez l'email comme identifiant unique
    }
}