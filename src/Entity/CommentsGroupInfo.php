<?php

namespace Netliva\CommentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'netliva_commenter_group_info')]
#[ORM\UniqueConstraint(name: 'identify', columns: ['`group`', '`key`'])]
class CommentsGroupInfo
{
	/**
     * @var integer
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

	/**
     * @var string
     */
    #[ORM\Column(name: '`group`', type: 'string', length: 255)]
    private $group;

	/**
     * @var string
     */
    #[ORM\Column(name: '`key`', type: 'string', length: 255)]
    private $key;


	/**
     * @var array
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private $info;

	/**
	 * @return int
	 */
	public function getId (): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId (int $id): void
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getGroup (): string
	{
		return $this->group;
	}

	/**
	 * @param string $group
	 */
	public function setGroup (string $group): void
	{
		$this->group = $group;
	}

	/**
	 * @return string
	 */
	public function getKey (): string
	{
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function setKey (string $key): void
	{
		$this->key = $key;
	}

	/**
	 * @return array
	 */
	public function getInfo (): array
	{
		return $this->info;
	}

	/**
	 * @param array $info
	 */
	public function setInfo (array $info): void
	{
		$this->info = $info;
	}


}
