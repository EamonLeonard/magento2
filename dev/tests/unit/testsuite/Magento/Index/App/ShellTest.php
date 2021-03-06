<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Index
 * @subpackage  unit_tests
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Index\App;

class ShellTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Index\App\Shell
     */
    protected $_entryPoint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_shellFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_responseMock;

    protected function setUp()
    {
        $this->_shellFactory = $this->getMock('Magento\Index\Model\ShellFactory', array('create'), array(), '', false);
        $this->_responseMock = $this->getMock('Magento\Framework\App\Console\Response', array(), array(), '', false);
        $this->_entryPoint = new \Magento\Index\App\Shell('indexer.php', $this->_shellFactory, $this->_responseMock);
    }

    /**
     * @param boolean $shellHasErrors
     * @dataProvider processRequestDataProvider
     */
    public function testProcessRequest($shellHasErrors)
    {
        $shell = $this->getMock('Magento\Index\Model\Shell', array(), array(), '', false);
        $shell->expects($this->once())->method('hasErrors')->will($this->returnValue($shellHasErrors));
        $shell->expects($this->once())->method('run');
        if ($shellHasErrors) {
            $this->_responseMock->expects($this->once())->method('setCode')->with(-1);
        } else {
            $this->_responseMock->expects($this->once())->method('setCode')->with(0);
        }
        $this->_shellFactory->expects($this->any())->method('create')->will($this->returnValue($shell));

        $this->_entryPoint->launch();
    }

    /**
     * @return array
     */
    public function processRequestDataProvider()
    {
        return array(array(true), array(false));
    }
}
