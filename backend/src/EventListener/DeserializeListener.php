<?php

namespace App\EventListener;

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class DeserializeListener
{
  private $serializer;
  private $serializerContextBuilder;
  private $encoder;
  private $token_storage;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(SerializerInterface $serializer, SerializerContextBuilderInterface $serializerContextBuilder, UserPasswordEncoderInterface $encoder, TokenStorageInterface $token_storage)
    {
        $this->serializer = $serializer;
        $this->serializerContextBuilder = $serializerContextBuilder;
        $this->encoder = $encoder;
        $this->token_storage = $token_storage;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();
        try {
         $attributes = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
         return;
        }

        if (
            'POST' != $method
            || ('App\Entity\Message' != $attributes['input_class'] &&
                'App\Entity\User' != $attributes['input_class'] )
        ) {
            return;
        }
        else{
        $context = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);
        if (isset($context['input_class'])) {
            $context['resource_class'] = $context['input_class'];
        }
        $data = $request->attributes->get('data');
        if (null !== $data) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }
        $format = $this->getFormat($request);
        $requestContent = $request->getContent();

        if('App\Entity\Message' == $attributes['input_class']){
          $message = $this->serializer->deserialize(
              $requestContent, $attributes['input_class'], $format, $context
          );
          $message->setAuthor($this->token_storage->getToken()->getUser());
          $request->attributes->set(
              'data',
              $message
          );
        }
        if('App\Entity\User' == $attributes['input_class']){
          $user = $this->serializer->deserialize(
              $requestContent, $attributes['input_class'], $format, $context
          );
          $user->setPassword($this->encoder->encodePassword($user, $user->getPlainPassword()));

          $request->attributes->set(
              'data',
              $user
          );
        }
      }
    }

    /**
     * Extracts the format from the Content-Type header and check that it is supported.
     *
     * @throws NotAcceptableHttpException
     */
    private function getFormat(Request $request): string
    {
        /**
         * @var string|null
         */
        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType) {
            throw new NotAcceptableHttpException('The "Content-Type" header must exist.');
        }

        $format = $request->getFormat($contentType);
        return $format;
    }

}


 ?>
