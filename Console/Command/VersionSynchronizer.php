<?php
/**
 * @author    Ruslan <rusalndovg291@gmail.com>
 */

namespace Rus\ModulesVersionSynchronizer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Module\DbVersionInfo;
use Magento\Framework\Model\ResourceModel\Db\Context;
/**
 * CLI command which regenerates category URLs.
 *
 * @author    Ruslan <rusalndovg291@gmail.com>
 * @copyright 2017 Rus
 * @link      http://www.de.Rus.com/
 */
class VersionSynchronizer extends Command
{
    /**
     * @var DbVersionInfo
     */
    protected $_dbVersionInfo;

    /**
     * @var Context
     */
    protected $_context;
    /**
     * VersionSynchronizer constructor.
     *
     * @param DbVersionInfo $dbVersionInfo
     * @param Context $context
     */
    public function __construct(
        DbVersionInfo $dbVersionInfo,
        Context $context
    ) {
        $this->_dbVersionInfo = $dbVersionInfo;
        $this->_context = $context;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('sync:versions')
            ->setDescription('Synchronize modules versions.')
            ->addOption(
                'module_name',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Use specific module name.'
            )->addOption(
                'silent',
                null,
                InputOption::VALUE_NONE,
                'Disable console output.'
            );
        return parent::configure();
    }

    /**
     * Executes the current command.
     *
     * Use case for cli command is: bin/magento sync:versions
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $silent = $input->getOption('silent');
        $dbVersionErrors = $this->_dbVersionInfo->getDbVersionErrors();
        foreach ($dbVersionErrors as $dbError) {
            $status = version_compare($dbError['required'], $dbError['current']);
            if ($status == \Magento\Framework\Setup\ModuleDataSetupInterface::VERSION_COMPARE_LOWER) {
                $resource = new \Magento\Framework\Module\ModuleResource($this->_context);
                if ($dbError['type'] === 'schema') {
                    $resource->setDbVersion($dbError['module'], $dbError['required']);
                    $this->_printStatus($silent, $dbError, $output);
                } elseif ($dbError['type'] === 'data') {
                    $resource->setDataVersion($dbError['module'], $dbError['required']);
                    $this->_printStatus($silent, $dbError, $output);
                }
            }
        }
    }

    /**
     * Print status of updated modules.
     *
     * @param $silent
     * @param $dbError
     * @param $output
     *
     * @return void
     */
    protected function _printStatus($silent, $dbError, $output)
    {
        if (!$silent) {
            $output->writeln(
                'Downgrade ' . $dbError['module'] . ' ' . $dbError['type'] . ' from ' .
                $dbError['current'] . ' to ' . $dbError['required']
            );
        }
    }

}
