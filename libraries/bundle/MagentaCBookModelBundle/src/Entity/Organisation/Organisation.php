<?php

namespace Magenta\Bundle\CBookModelBundle\Entity\Organisation;

use Bean\Component\Organization\Model\Organization as OrganizationModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Magenta\Bundle\CBookModelBundle\Entity\Book\Book;
use Magenta\Bundle\CBookModelBundle\Entity\Classification\Category;
use Magenta\Bundle\CBookModelBundle\Entity\Classification\Context;
use Magenta\Bundle\CBookModelBundle\Entity\Media\Media;
use Magenta\Bundle\CBookModelBundle\Entity\Person\Person;
use Magenta\Bundle\CBookModelBundle\Entity\System\System;
use Magenta\Bundle\CBookModelBundle\Entity\User\User;

/**
 * @ORM\Entity()
 * @ORM\Table(name="organisation__organisation")
 */
class Organisation extends OrganizationModel
{
    const PRE_CREATED_CONTEXT_ORG_LOGO = 'organisation_logo';

    /**
     * @var int|null
     * @ORM\Id
     * @ORM\Column(type="integer",options={"unsigned":true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        $this->books = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->memberGroups = new ArrayCollection();
        $this->adminUsers = new ArrayCollection();
        $this->individualMembers = new ArrayCollection();
        $this->mediaAssets = new ArrayCollection();
        $this->conversations = new ArrayCollection();
    }

    public function isAuthByUsernamePassword()
    {
        return !empty($this->authByUsernamePassword);
    }

    public function getPublicConversation()
    {
        return $this->conversations->first();
    }

    /**
     * @param Person $person
     *
     * @return IndividualMember|null
     */
    public function getIndividualMemberFromPerson(Person $person)
    {
        $c = Criteria::create();
        $c->where(Criteria::expr()->eq('person', $person));
        $col = $this->individualMembers->matching($c);
        if (empty($f = $col->first())) {
            return null;
        }

        return $f;
    }

    /**
     * @return Collection
     */
    public function getPublishedBooks(): Collection
    {
        $c = Criteria::create();
        $expr = Criteria::expr();
        $c->where($expr->andX(
            $expr->eq('status', Book::STATUS_PUBLISHED),
            $expr->eq('enabled', true)
        ));

        return $this->books->matching($c);
    }

    /**
     * @return Collection
     */
    public function getDraftBooksHavingPreviousVersions(): Collection
    {
        $c = Criteria::create();
        $expr = Criteria::expr();
        $c->where($expr->andX(
            $expr->eq('status', Book::STATUS_DRAFT),
//            $expr->eq('enabled', false),
            $expr->neq('previousVersion', null)
        ));

        return $this->books->matching($c);
    }

    /**
     * @ORM\ManyToMany(
     *     targetEntity="Magenta\Bundle\CBookModelBundle\Entity\User\User",
     *     mappedBy="adminOrganisations", cascade={"persist","merge"}
     * )
     *
     * @var Collection ;
     */
    protected $adminUsers;

    public function addAdminUser(User $user)
    {
        $this->adminUsers->add($user);
        if (!$user->getAdminOrganisations()->contains($this)) {
            $user->addAdminOrganisation($this);
        }
    }

    public function removeAdminUser(User $user)
    {
        $this->adminUsers->removeElement($user);
        $user->removeAdminOrganisation($this);
    }

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Magenta\Bundle\CBookModelBundle\Entity\System\AccessControl\ACRole", mappedBy="organisation", cascade={"persist","merge"}, orphanRemoval=true)
     */
    protected $roles;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Messaging\Conversation",
     *     mappedBy="organisation", cascade={"persist"}, orphanRemoval=true
     * )
     *
     * @var Collection ;
     */
    protected $conversations;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Messaging\Message",
     *     mappedBy="organisation", cascade={"persist"}, orphanRemoval=true
     * )
     *
     * @var Collection ;
     */
    protected $messages;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Organisation\IndividualGroup",
     *     mappedBy="organization", cascade={"persist"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"position"="ASC"})
     *
     * @var Collection ;
     */
    protected $memberGroups;

    public function addMemberGroup(IndividualGroup $group)
    {
        $this->memberGroups->add($group);
        $group->setOrganization($this);
    }

    public function removeMemberGroup(IndividualGroup $group)
    {
        $this->memberGroups->removeElement($group);
        $group->setOrganization(null);
    }

    /**
     * @ORM\OneToMany(
     *     targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Classification\Category",
     *     mappedBy="organisation", cascade={"persist"}, orphanRemoval=true
     * )
     * @ORM\OrderBy({"position"="ASC"})
     *
     * @var Collection ;
     */
    protected $categories;

    public function getRootCategoriesByContext(Context $context)
    {
        $c = Criteria::create();
        $expr = Criteria::expr();
        $c->where($expr->andX(
            $expr->eq('context', $context),
            $expr->isNull('parent')
        ));

        return $this->categories->matching($c);
    }

    public function addCategory(Category $category)
    {
        $this->categories->add($category);
        $category->setOrganisation($this);
    }

    public function removeCategory(Category $category)
    {
        $this->categories->removeElement($category);
        $category->setOrganisation(null);
    }

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Book\Book", cascade={"persist","merge"}, orphanRemoval=true, mappedBy="organisation")
     */
    protected $books;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Organisation\IndividualMember", mappedBy="organization")
     */
    protected $individualMembers;

    public function addIndividualMember(IndividualMember $member)
    {
        $this->individualMembers->add($member);
        $member->setOrganization($this);
    }

    public function removeIndividualMember(IndividualMember $member)
    {
        $this->individualMembers->removeElement($member);
        $member->setOrganization(null);
    }

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Media\Media", mappedBy="organisation")
     */
    protected $mediaAssets;

    /**
     * @var System|null
     * @ORM\ManyToOne(targetEntity="Magenta\Bundle\CBookModelBundle\Entity\System\System", inversedBy="organisations", cascade={"persist","merge"})
     * @ORM\JoinColumn(name="id_system", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $system;

    /**
     * @var Media|null
     * @ORM\OneToOne(targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Media\Media", mappedBy="logoOrganisation", cascade={"persist","merge"})
     */
    protected $logo;

    /**
     * @var Media|null
     * @ORM\OneToOne(targetEntity="Magenta\Bundle\CBookModelBundle\Entity\Media\Media", mappedBy="appIconOrganisation", cascade={"persist","merge"})
     */
    protected $appIcon;

    /**
     * @param Media|null $logo
     */
    public function setLogo(?Media $logo): void
    {
        $this->logo = $logo;
        if (!empty($logo)) {
            $logo->setLogoOrganisation($this);
            $logo->setOrganization($this);
        }
    }

    /**
     * @param Media|null $appIcon
     */
    public function setAppIcon(?Media $appIcon): void
    {
        $this->appIcon = $appIcon;
        if (!empty($appIcon)) {
            $appIcon->setAppIconOrganisation($this);
            $appIcon->setOrganization($this);
        }
    }

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $synchronisedAt;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $wellnessId;

    /**
     * @var boolean|null
     * @ORM\Column(type="boolean",nullable=true)
     */
    protected $authByUsernamePassword;

    /**
     * @var boolean|null
     * @ORM\Column(type="boolean",nullable=true)
     */
    protected $linkedToWellness;

    /**
     * @var string|null
     * @ORM\Column(length=150, nullable=true)
     */
    protected $regNo;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    protected $wellnessPin;
    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    protected $wellnessEmployeeCode;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $slug;

    /**
     * @return Media|null
     */
    public function getLogo(): ?Media
    {
        return $this->logo;
    }

    /**
     * @return Collection
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    /**
     * @param Collection $books
     */
    public function setBooks(Collection $books): void
    {
        $this->books = $books;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     */
    public function setCode(string $code = null): void
    {
        $this->code = $code;
    }

    /**
     * @return Collection
     */
    public function getIndividualMembers(): Collection
    {
        return $this->individualMembers;
    }

    /**
     * @param Collection $individualMembers
     */
    public function setIndividualMembers(Collection $individualMembers): void
    {
        $this->individualMembers = $individualMembers;
    }

    /**
     * @return Collection
     */
    public function getAdminUsers(): Collection
    {
        return $this->adminUsers;
    }

    /**
     * @param Collection $adminUsers
     */
    public function setAdminUsers(Collection $adminUsers): void
    {
        $this->adminUsers = $adminUsers;
    }

    /**
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @param Collection $roles
     */
    public function setRoles(Collection $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     */
    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return bool|null
     */
    public function getLinkedToWellness(): ?bool
    {
        return $this->linkedToWellness;
    }

    /**
     * @return bool
     */
    public function isLinkedToWellness(): bool
    {
        return !empty($this->linkedToWellness);
    }

    /**
     * @param bool|null $linkedToWellness
     */
    public function setLinkedToWellness(?bool $linkedToWellness): void
    {
        $this->linkedToWellness = $linkedToWellness;
    }

    /**
     * @return int
     */
    public function getWellnessId(): int
    {
        return $this->wellnessId;
    }

    /**
     * @param int $wellnessId
     */
    public function setWellnessId(int $wellnessId): void
    {
        $this->wellnessId = $wellnessId;
    }

    /**
     * @return null|string
     */
    public function getRegNo(): ?string
    {
        return $this->regNo;
    }

    /**
     * @param null|string $regNo
     */
    public function setRegNo(?string $regNo): void
    {
        $this->regNo = $regNo;
    }

    /**
     * @return \DateTime|null
     */
    public function getSynchronisedAt(): ?\DateTime
    {
        return $this->synchronisedAt;
    }

    /**
     * @param \DateTime|null $synchronisedAt
     */
    public function setSynchronisedAt(?\DateTime $synchronisedAt): void
    {
        $this->synchronisedAt = $synchronisedAt;
    }

    /**
     * @return Media|null
     */
    public function getAppIcon(): ?Media
    {
        return $this->appIcon;
    }

    /**
     * @return Collection
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param Collection $categories
     */
    public function setCategories(Collection $categories): void
    {
        $this->categories = $categories;
    }

    /**
     * @return Collection
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    /**
     * @param Collection $conversations
     */
    public function setConversations(Collection $conversations): void
    {
        $this->conversations = $conversations;
    }

    /**
     * @return Collection
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @param Collection $messages
     */
    public function setMessages(Collection $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * @return bool|null
     */
    public function getAuthByUsernamePassword(): ?bool
    {
        return $this->authByUsernamePassword;
    }

    /**
     * @param bool|null $authByUsernamePassword
     */
    public function setAuthByUsernamePassword(?bool $authByUsernamePassword): void
    {
        $this->authByUsernamePassword = $authByUsernamePassword;
    }
    
    /**
     * @return System|null
     */
    public function getSystem(): ?System
    {
        return $this->system;
    }
    
    /**
     * @param System|null $system
     */
    public function setSystem(?System $system): void
    {
        $this->system = $system;
    }
}
