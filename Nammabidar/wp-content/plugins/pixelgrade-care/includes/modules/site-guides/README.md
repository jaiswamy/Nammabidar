Custom Welcome Guide is a WordPress plugin, inspired by the [Guide](https://developer.wordpress.org/block-editor/reference-guides/components/guide/) Gutenberg component that pops up when the user opens the WordPress editor for the first time. The plugin lets you create onboarding user introduction tours to guide content creators throughout the admin area of the website.

### Installation
Clone or download this repository, unzip it and paste it inside the ```wp-content => plugins``` folder in your WordPress installation. Activate the plugin from the WordPress admin.

If you are a developer and you want to contribute to this project or fork it, then read on. Go to the plugin's root folder and run:

    npm i
    npm start

This step is required if you want to enable support for ES6+ and JSX. After you finish your work, run ```npm run build``` to generate the code for production.

### Technical Information
This plugin is bootstrapped by running ```npx @wordpress/create-block custom-welcome-guide```. See the [Block Api WordPress documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/) for more information.

### Useful Links

1. [WordPress Block Api Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/)

2. [Gutenberg Core Component: Guide](https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/guide)

3. [Setting Up your First Gutenberg Project](https://blog.logrocket.com/setting-up-first-gutenberg-project/)

4. [WordPress Gutenberg PluginDocumentSettingPanel not working with controls?](https://stackoverflow.com/questions/58607970/wordpress-gutenberg-plugindocumentsettingpanel-not-working-with-controls)

5. [Add Custom Welcome Guide Inside the WordPress Block Editor](https://css-tricks.com/adding-a-custom-welcome-guide-to-the-wordpress-block-editor/)

6. [Building a required modal in the WordPress Block Editor Part 1](https://aurooba.com/building-a-required-modal-in-the-wordpress-block-editor-1/)

7. [Add Entry Points to the WordPress Create Block Script](https://wholesomecode.ltd/guides/entry-points-wordpress-create-block-script/)

8. [Create a Settings Page using WordPress Block Editor (Gutenberg) Components](https://wholesomecode.ltd/wordpress/create-settings-page-wordpress-gutenberg-components/)

### Next Steps
Check issues in Github.

### License
* License: [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.html)