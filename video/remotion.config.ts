import { Config } from '@remotion/cli/config';
import { enableTailwind } from '@remotion/tailwind-v4';

Config.setPublicDir('../public/assets/media/app');
Config.setVideoImageFormat('jpeg');
Config.setOverwriteOutput(true);
Config.overrideWebpackConfig((currentConfiguration) => enableTailwind(currentConfiguration));
