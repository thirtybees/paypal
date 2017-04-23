module.exports = function(grunt) {

    grunt.initConfig({
        compress: {
            main: {
                options: {
                    archive: 'paypal.zip'
                },
                files: [
                    {src: ['classes/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: ['controllers/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: ['docs/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: ['sql/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: ['translations/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: ['override/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: ['upgrade/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: ['vendor/**'], dest: 'paypal/', filter: 'isFile'},
                    {src: [
                      'views/**',
                        '!views/js/paypalcheckout/node_modules/**',
                        '!views/js/paypalpanel/node_modules/**',
                        '!views/js/paypalexport/node_modules/**',
                        '!views/js/paypalexport/dist/paypalexport.js.map',
                        '!views/js/paypalcheckout/dist/paypalcheckout.js.map',
                        '!views/js/paypalpanel/dist/paypalpanel.js.map',
                    ], dest: 'paypal/', filter: 'isFile'},
                    {src: 'config.xml', dest: 'paypal/'},
                    {src: 'index.php', dest: 'paypal/'},
                    {src: 'paypal.php', dest: 'paypal/'},
                    {src: 'logo.png', dest: 'paypal/'},
                    {src: 'logo.gif', dest: 'paypal/'},
                    {src: 'CHANGELOG.md', dest: 'paypal/'}
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-compress');

    grunt.registerTask('default', ['compress']);
};
