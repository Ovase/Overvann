<?php

namespace AppBundle\Repository;
use Doctrine\ORM\EntityRepository;

/**
 * PersonRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PersonRepository extends EntityRepository
{
	public function create($person)
	{
		$em = $this->getEntityManager();
		$em->persist($person);
		$em->flush();
		return $person;
	}

	public function findPersonsBySearch($searchTerm)
	{
		return $this->createQueryBuilder('Person')
			->select('Person')
			->where('Person.firstName LIKE :searchTerm')
			->orWhere('Person.lastName LIKE :searchTerm')
			->setParameter('searchTerm', '%'.$searchTerm.'%')
			->getQuery()
			->getResult();
	}
}
