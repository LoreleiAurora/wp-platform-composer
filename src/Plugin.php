<?php

namespace LoreleiAurora\WPPlatform\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface {

	/**
	 * Apply plugin modifications to composer
	 *
	 * @param Composer $composer
	 * @param IOInterface $io
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$installer = new Installer( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );

		$checkout_submodules = function () use ( $composer, $io ) {
			$io->write( "<info>Dumping package paths</info>" );

			$manager = $composer->getInstallationManager();

			$root_package = $composer->getPackage();

			$root_path = strtr( getcwd(), '\\', '/' );

			$paths = [];

			if ( $root_package->getName() !== '__root__' ) {
				$paths[ $root_package->getName() ] = ''; // resolves root package as absolute project root path
			}

			$packages = $composer->getRepositoryManager()->getLocalRepository()->getPackages();

			foreach ( $packages as $package ) {
				$name = $package->getName();

				$install_path = strtr( $manager->getInstallPath( $package ), '\\', '/' );

				$paths[ $name ] = substr( $install_path, strlen( $root_path ) );

				if ( $name === "loreleiaurora/wp-platform" ) {
					$io->write( "<info>Checking Out Submodules</info>" );

					system( "cd '${install_path}' && git submodule update --init --recursive", $retval );

					if ( $retval !== 0 ) {
						throw new \RuntimeException( "internal error - failed to checkout submodules" );
					}
				}
			}
		};

		$composer->getEventDispatcher()->addListener( "post-install-cmd", $checkout_submodules );
		$composer->getEventDispatcher()->addListener( "post-update-cmd", $checkout_submodules );
	}

}