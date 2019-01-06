<?php

namespace Bitty\Tests\Controller;

use Bitty\Container\ContainerAwareInterface;
use Bitty\Controller\AbstractController;
use Bitty\Http\Exception\InternalServerErrorException;
use Bitty\Router\UriGeneratorInterface;
use Bitty\View\ViewInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class AbstractControllerTest extends TestCase
{
    /**
     * @var AbstractController
     */
    protected $fixture = null;

    /**
     * @var ContainerInterface
     */
    protected $container = null;

    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);

        $this->fixture = $this->getMockForAbstractClass(AbstractController::class, [$this->container]);
    }

    public function testRedirectToRouteCallsContainer()
    {
        $uriGenerator = $this->createUriGenerator();

        $this->container->expects($this->once())
            ->method('get')
            ->with('uri.generator')
            ->willReturn($uriGenerator);

        $this->fixture->redirectToRoute(uniqid());
    }

    public function testRedirectToRouteCallsUriGenerator()
    {
        $name         = uniqid('name');
        $params       = [uniqid('param')];
        $uriGenerator = $this->createUriGenerator();

        $this->container->method('get')->willReturn($uriGenerator);

        $uriGenerator->expects($this->once())
            ->method('generate')
            ->with($name, $params);

        $this->fixture->redirectToRoute($name, $params);
    }

    public function testRedirectToRouteResponse()
    {
        $uri          = uniqid('uri');
        $uriGenerator = $this->createUriGenerator($uri);

        $this->container->method('get')->willReturn($uriGenerator);

        $actual = $this->fixture->redirectToRoute(uniqid());

        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals([$uri], $actual->getHeader('Location'));
        $this->assertEquals(302, $actual->getStatusCode());
    }

    public function testGetCallsContainer()
    {
        $id = uniqid('service');

        $this->container->expects($this->once())
            ->method('get')
            ->with($id);

        $this->fixture->get($id);
    }

    public function testGetResponse()
    {
        $value = uniqid('value');

        $this->container->method('get')->willReturn($value);

        $actual = $this->fixture->get(uniqid());

        $this->assertEquals($value, $actual);
    }

    public function testRenderCallsContainer()
    {
        $view = $this->createView();

        $this->container->expects($this->once())
            ->method('get')
            ->with('view')
            ->willReturn($view);

        $this->fixture->render(uniqid());
    }

    public function testRenderThrowsException()
    {
        $message = 'Container service "view" must be an instance of '.ViewInterface::class;
        $this->expectException(InternalServerErrorException::class);
        $this->expectExceptionMessage($message);

        $this->fixture->render(uniqid());
    }

    public function testRenderCallsView()
    {
        $template = uniqid('template');
        $data     = [uniqid('data')];
        $view     = $this->createView();

        $this->container->method('get')->willReturn($view);

        $view->expects($this->once())
            ->method('render')
            ->with($template, $data);

        $this->fixture->render($template, $data);
    }

    public function testRenderResponse()
    {
        $html = uniqid('html');
        $view = $this->createView($html);

        $this->container->method('get')->willReturn($view);

        $actual = $this->fixture->render(uniqid());

        $this->assertInstanceOf(ResponseInterface::class, $actual);
        $this->assertEquals($html, (string) $actual->getBody());
    }

    /**
     * Creates a URI generator.
     *
     * @param string $uri
     *
     * @return UriGeneratorInterface
     */
    protected function createUriGenerator($uri = '')
    {
        $uriGenerator = $this->createMock(UriGeneratorInterface::class);
        $uriGenerator->method('generate')->willReturn($uri);

        return $uriGenerator;
    }

    /**
     * Creates a view.
     *
     * @param string $html
     *
     * @return ViewInterface
     */
    protected function createView($html = '')
    {
        $view = $this->createMock(ViewInterface::class);
        $view->method('render')->willReturn($html);

        return $view;
    }
}
