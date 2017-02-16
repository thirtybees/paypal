module.exports = function(grunt) {

    grunt.initConfig({
        compress: {
            main: {
                options: {
                    archive: 'myparcel.zip'
                },
                files: [
                    {src: ['classes/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: ['controllers/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: ['docs/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: ['sql/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: ['translations/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: ['override/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: ['upgrade/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: ['vendor/**'], dest: 'myparcel/', filter: 'isFile'},
                    {src: [
                      'views/**',
                        '!views/js/myparcelcheckout/node_modules/**',
                        '!views/js/myparcelpanel/node_modules/**',
                        '!views/js/myparcelexport/node_modules/**',
                        '!views/js/myparcelexport/dist/myparcelexport.js.map',
                        '!views/js/myparcelcheckout/dist/myparcelcheckout.js.map',
                        '!views/js/myparcelpanel/dist/myparcelpanel.js.map',
                    ], dest: 'myparcel/', filter: 'isFile'},
                    {src: 'config.xml', dest: 'myparcel/'},
                    {src: 'index.php', dest: 'myparcel/'},
                    {src: 'myparcel.php', dest: 'myparcel/'},
                    {src: 'logo.png', dest: 'myparcel/'},
                    {src: 'logo.gif', dest: 'myparcel/'},
                    {src: 'CHANGELOG.md', dest: 'myparcel/'}
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-compress');

    grunt.registerTask('default', ['compress']);
};
