<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Measure;
use AppBundle\Entity\Project;
use AppBundle\Entity\ProjectImage;
use AppBundle\Form\CreateProjectForm;
use AppBundle\Form\ProjectType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/* // Hidden imports that may be used if the IvoryGoogleMaps library is installed
use Ivory\GoogleMap\Map;
use Ivory\GoogleMap\MapTypeId;
use Ivory\GoogleMap\Base\Coordinate;
use Ivory\GoogleMap\Overlays\Animation;
use Ivory\GoogleMap\Overlays\Icon;
use Ivory\GoogleMap\Overlays\Marker;
use Buzz\Browser;
//use Ivory\GoogleMapBundle\Model\Map;
//use Ivory\GoogleMap\Overlays\MarkerShape;
*/

class ProjectController extends Controller
{
    public function showAction(Request $request)
    {
        $requestID = $request->get('id');
        $project = $this->getDoctrine()->getManager()->getRepository('AppBundle:Project')->find($requestID);
        # The API key to use on Google Maps Embed API is defined as a global parameter accessable through the service container.
        $canEdit = false;
        if ($this->get('security.authorization_checker')->isGranted('ROLE_EDITOR') || $this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') && $this->getUser()->canEditProject($project)) {
            $canEdit = true;
        }
        return $this->render('project/project.html.twig', array('project' => $project,
            'key' => $this->container->getParameter('api_key'),
            'canEdit' => $canEdit));
    }

    public function createAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException('Du må være logget inn og aktivert av en redaktør for å lage et prosjekt');
        }
        $em = $this->getDoctrine()->getManager();
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form['imageFiles']->getData();
            foreach ($images as $image) {
                if ($image != null) {
                    $project->getImages()->add($this->get('image_service')->upload($image));
                }
            }
            $user = $this->getUser();
            $user->addProject($project);
            $em->persist($project);
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('project', array( 'id' => $project->getId() ));
        }
        return $this->render(
            'project/create.html.twig', array(
                'form' => $form->createView()
            )
        );
    }

    public function create2Action(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException('Du må være logget inn og aktivert av en redaktør for å lage et prosjekt');
        }

        $em = $this->getDoctrine()->getManager();
        $project = new Project();
        
        $flow = $this->get('ovase.form.flow.editProject'); // must match the flow's service id
        $flow->bind($project);
        $form = $flow->createForm();
        if ($flow->isValid($form)) {

            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // Form for the next step
                $form = $flow->createForm();
            } else {
                // Flow finished
                $em = $this->getDoctrine()->getManager();
                $em->persist($project);
                $em->flush();
                $flow->reset();
                return $this->redirect($this->generateUrl('home')); // redirect when done
            }
        }
        return $this->render('project/create2.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
        ));

    }

    public function editAction(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException("Du må være logget inn og aktivert av en redaktør for å se denne siden");
        }

        $requestID = $request->get('id');
        $project = $this->getDoctrine()->getManager()->getRepository('AppBundle:Project')->find($requestID);

        if (!$this->getUser()->canEditProject($project) && !$this->get('security.authorization_checker')->isGranted('ROLE_EDITOR')) {
            throw $this->createAccessDeniedException("Du har ikke redigeringsrettigheter til dette prosjektet");
        }

        $form = $this->createForm(ProjectType::class, $project, array('method' => 'PUT'));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFiles = $form['imageFiles']->getData();
            $urls = clone $project->getImages(); // returns an array, ja?
            foreach ($imageFiles as $image) {
                if ($image != null) {
                    $urls->add($this->get('image_service')->upload($image));
                }
            }
            $project->setImages($urls);
            $project->incrementVersion();
            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();
            return $this->redirectToRoute('project', array( 'id' => (string)$requestID) );
        }
        return $this->render(
            'project/edit.html.twig', array(
                'form' => $form->createView()
            )
        );
    }

    public function edit2Action(Request $request)
    {
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException("Du må være logget inn og aktivert av en redaktør for å se denne siden");
        }

        $requestID = $request->get('id');
        $project = $this->getDoctrine()->getManager()->getRepository('AppBundle:Project')->find($requestID);

        if (!$this->getUser()->canEditProject($project) && !$this->get('security.authorization_checker')->isGranted('ROLE_EDITOR')) {
            throw $this->createAccessDeniedException("Du har ikke redigeringsrettigheter til dette prosjektet");
        }

        $em = $this->getDoctrine()->getManager();

        /* Remove later */
        $logger = $this->get('logger');

        // Store original images to know if any were removed
        $originalImages = new ArrayCollection();
        foreach ($project->getImages() as $img) {
            $originalImages->add($img);
        }
        
        $flow = $this->get('ovase.form.flow.editProject'); // must match the flow's service id

        // Read 
        $flow->bind($project);
        $form = $flow->createForm();

        $logger->info("FormData: " . $form->getData() );

        if ($flow->isValid($form)) {

            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                // Delete images that were removed
                foreach ($originalImages as $origImg) {
                     if ($project->getImages()->contains($origImg) === false) {
                        $em->remove($origImg);
                    }
                }
                // flow finished
                $em = $this->getDoctrine()->getManager();
                $em->persist($project);
                $em->flush();

                $flow->reset(); // remove step data from the session

                return $this->redirect($this->generateUrl('home')); // redirect when done
            }
        }

        return $this->render('project/edit2.html.twig', array(
            'form' => $form->createView(),
            'flow' => $flow,
        ));

    }

}
