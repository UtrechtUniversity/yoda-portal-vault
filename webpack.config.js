var webpack = require('webpack');
var path = require('path');

var BUILD_DIR = path.resolve(__dirname, 'public/static/js/metadata');
var APP_DIR = path.resolve(__dirname, 'app');

var config = {
    entry: APP_DIR + '/index.js',
    output: {
        path: BUILD_DIR,
        filename: 'form.js'
    },
    module: {
        rules: [
            {
                test: /\.js?/,
                include : APP_DIR,
                use: {
                    loader: 'babel-loader'
                }
            }
        ]
    }
};

module.exports = config;
