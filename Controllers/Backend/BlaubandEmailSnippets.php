<?php

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Shop\Shop;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Snippet\Snippet;

class Shopware_Controllers_Backend_BlaubandEmailSnippets extends \Enlight_Controller_Action implements CSRFWhitelistAware
{
    /** @var Shopware_Components_Snippet_Manager */
    private $snippetsManager;

    /** @var ModelManager */
    private $modelManager;

    /** @var array */
    private $shops;

    public function getWhitelistedCSRFActions()
    {
        return [
            'index',
            'save',
            'delete',
        ];
    }

    public function preDispatch()
    {
        $this->snippetsManager = $this->container->get('snippets');
        $this->modelManager = $this->container->get('models');

        $repository = $this->modelManager->getRepository(Shop::class);
        $this->shops = $repository->findAll();

        $pluginPath = $this->container->getParameter('shopware.plugin_directories')['ShopwarePlugins'];

        $this->view->addTemplateDir($pluginPath . "BlaubandEmail/Resources/views");
        $this->view->addTemplateDir(__DIR__ . "/../../Resources/views");
    }

    public function indexAction()
    {
        $snippetName = $this->request->getParam('snippetName');
        $snippetValue = $this->request->getParam('snippetValue');

        $snippets = [];

        if (!empty($snippetName)) {
            /** @var Shop $shop */
            foreach ($this->shops as $shop) {
                $this->snippetsManager->setShop($shop);
                $value = $this->snippetsManager
                    ->getNamespace(\BlaubandEmailSnippets\Subscribers\Backend::$customSnippetNamespace)
                    ->get($snippetName);

                $snippets[$shop->getId()]['shopName'] = $shop->getName();
                $snippets[$shop->getId()]['shopLocale'] = $shop->getLocale()->getLocale();
                $snippets[$shop->getId()]['value'] = $value;
            }

            $this->view->assign('snippets', $snippets);
            $this->view->assign('snippetName', $snippetName);
            $this->view->assign('saveSuccess', $this->request->getParam('saveSuccess'));
            return;
        }

        if (!empty($snippetValue)) {
            foreach ($this->shops as $shop) {
                $this->snippetsManager->setShop($shop);

                $snippets[$shop->getId()]['shopName'] = $shop->getName();
                $snippets[$shop->getId()]['shopLocale'] = $shop->getLocale()->getLocale();
                $snippets[$shop->getId()]['value'] = $snippetValue;
            }

            $snippetName = ucwords($snippetValue);
            $snippetName = str_replace(' ', '', $snippetName);
            $snippetName = substr($snippetName, 0, 15);

            $this->view->assign('snippets', $snippets);
            $this->view->assign('snippetName', $snippetName);
            $this->view->assign('saveSuccess', $this->request->getParam('saveSuccess'));
            return;
        }

    }

    public function saveAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $params = $this->request->getParams();
        $snippetName = $params['snippetName'];
        $newSnippetName = $params['newSnippetName'];
        $snippetNamespace = \BlaubandEmailSnippets\Subscribers\Backend::$customSnippetNamespace;
        $snippetRepository = $this->modelManager->getRepository(Snippet::class);

        try {
            $this->validateSave($params);
        } catch (Exception $exception) {
            $this->Response()->setBody(json_encode(['success' => false, 'message' => $exception->getMessage()]));
            $this->Response()->setHeader('Content-type', 'application/json', true);
            return;
        }

        /** @var Shop $shop */
        foreach ($this->shops as $shop) {
            if (!isset($params['snippet-' . $shop->getId()])) {
                continue;
            }

            $value = $params['snippet-' . $shop->getId()];

            $snippet = $snippetRepository->findOneBy(
                [
                    'shopId' => $shop->getId(),
                    'localeId' => $shop->getLocale()->getId(),
                    'namespace' => $snippetNamespace,
                    'name' => $snippetName
                ]
            );

            if (empty($snippet)) {
                $snippet = new Snippet();
                $snippet->setShopId($shop->getId());
                $snippet->setLocaleId($shop->getLocale()->getId());
                $snippet->setNamespace($snippetNamespace);
                $snippet->setName($newSnippetName);

                $this->modelManager->persist($snippet);
            }

            $snippet->setValue($value);
            $snippet->setName($newSnippetName);
            $snippet->setUpdated();
        }

        $this->modelManager->flush();

        $this->Response()->setBody(json_encode(['success' => true]));
        $this->Response()->setHeader('Content-type', 'application/json', true);
    }

    public function deleteAction(){
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $params = $this->request->getParams();
        $snippetName = $params['snippetName'];
        $snippetNamespace = \BlaubandEmailSnippets\Subscribers\Backend::$customSnippetNamespace;
        $snippetRepository = $this->modelManager->getRepository(Snippet::class);

        $snippets = $snippetRepository->findBy(
            [
                'namespace' => $snippetNamespace,
                'name' => $snippetName
            ]
        );
        foreach ($snippets as $snippet){
            $this->modelManager->remove($snippet);
        }

        $this->modelManager->flush();

        $this->Response()->setBody(json_encode(['success' => true]));
        $this->Response()->setHeader('Content-type', 'application/json', true);
    }

    private function validateSave($params)
    {
        $newSnippetName = $params['newSnippetName'];
        $snippetNamespace = \BlaubandEmailSnippets\Subscribers\Backend::$customSnippetNamespace;
        $snippetRepository = $this->modelManager->getRepository(Snippet::class);

        $snippet = $snippetRepository->findOneBy(
            [
                'namespace' => $snippetNamespace,
                'name' => $newSnippetName
            ]
        );

        if(!empty($snippet)){
            throw new Exception($newSnippetName.' '. $this->snippetsManager
                    ->getNamespace('blauband/mail')
                    ->get('errorAlreadyExists'));
        }

        if (preg_match('/[^A-Za-z0-9 ]/', $newSnippetName))
        {
            throw new Exception($newSnippetName.' '. $this->snippetsManager
                    ->getNamespace('blauband/mail')
                    ->get('noSpecialCharacter'));
        }
    }
}
