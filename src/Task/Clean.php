<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile\Task;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use OpenShareFile\Utils\Clean as UtilsClean;


/**
 * Clean task class
 *
 * @package     OpenShareFile\Task
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Clean extends Command
{
    /**
     * Configure the task
     *
     * @access  protected
     * @return  void
     */
    protected function configure()
    {
        $this
            ->setName('clean')
            ->setDescription('Clean the files which have live more than lifetime');
    }
    
    
    /**
     * Execute task
     *
     * @param   \Symfony\Component\Console\Input\InputInterface     $input  The input arguments class
     * @param   \Symfony\Component\Console\Output\OutputInterface   $output The ouput class
     * @return  void
     * @access  protected
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Start task</info>');
        
        UtilsClean::run($output);
        
        $output->writeln('<info>End task</info>');
    }
}
