<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Company;
use AppBundle\Form\CompanyType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CompanyController extends Controller
{
	public function showAction(Request $request)
	{

		$requestID = $request->get('id');
		$company = $this->getDoctrine()->getManager()->getRepository('AppBundle:Company')->find($requestID);
		return $this->render(':actor:company.html.twig', array('company' => $company, 'key'=> $this->container->getParameter('api_key')));
	}

    public function createAction(Request $request)
    {
        if(!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY'))
        {
            throw $this->createAccessDeniedException('Du må være logget inn for å definere et selskap');
        }
        $em = $this->getDoctrine()->getManager();
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

		if($form->isSubmitted()){
            $url = $this->get('image_service')->upload($form['image']->getData());
            $company->setImage($url);
			$em->persist($company);
            $user = $this->getUser();
            $user->addActor($company);
			$em->persist($user);
			$em->flush();
			return $this->redirect('/selskap/' . $company->getId());
		}
		return $this->render(
			'actor/create_company.html.twig', array(
				'form' => $form -> createView()
			)
		);
	}
}