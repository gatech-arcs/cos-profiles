const path = require('path');
const fs = require('fs');
const { styles } = require('@ckeditor/ckeditor5-dev-utils');
const TerserPlugin = require('terser-webpack-plugin');

function getDirectories(srcpath) {
  return fs
    .readdirSync(srcpath)
    .filter((item) => fs.statSync(path.join(srcpath, item)).isDirectory());
}

module.exports = [];
getDirectories('./js/ckeditor5_plugins').forEach((dir) => {
  const config = {
    mode: 'production',
    // devtool: 'source-map',
    optimization: {
      minimize: true,
      // minimize: false,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            format: {
              comments: false,
            },
            mangle: false,
          },
          test: /\.js(\?.*)?$/i,
          extractComments: false,
        }),
      ],
    },
    entry: {
      path: path.resolve(
        __dirname,
        'js/ckeditor5_plugins',
        dir,
        'src/index.js',
      ),
    },
    output: {
      path: path.resolve(__dirname, './js/build'),
      filename: `${dir}.js`,
      library: {
        name: ['CKEditor5', dir],
        type: 'umd',
        export: 'default',
      },
      clean: true,
      globalObject: 'window'
    },
    module: {
      rules: [
        {
          test: /\.svg$/,
          use: 'raw-loader'
        },
        {
          test: /\.css$/,
          use: [
            {
              loader: 'style-loader',
              options: {
                injectType: 'singletonStyleTag',
                attributes: {
                  'data-cke': true
                }
              }
            },
            'css-loader',
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: styles.getPostCssConfig({
                  themeImporter: {
                    themePath: require.resolve('@ckeditor/ckeditor5-theme-lark')
                  },
                  minify: true
                })
              }
            }
          ]
        }
      ]
    },
    externals: {
      '@ckeditor/ckeditor5-core': {
        root: ['CKEditor5', 'core'],
        commonjs2: '@ckeditor/ckeditor5-core',
        commonjs: '@ckeditor/ckeditor5-core',
        amd: '@ckeditor/ckeditor5-core'
      },
      '@ckeditor/ckeditor5-ui': {
        root: ['CKEditor5', 'ui'],
        commonjs2: '@ckeditor/ckeditor5-ui',
        commonjs: '@ckeditor/ckeditor5-ui',
        amd: '@ckeditor/ckeditor5-ui'
      },
      '@ckeditor/ckeditor5-utils': {
        root: ['CKEditor5', 'utils'],
        commonjs2: '@ckeditor/ckeditor5-utils',
        commonjs: '@ckeditor/ckeditor5-utils',
        amd: '@ckeditor/ckeditor5-utils'
      },
      '@ckeditor/ckeditor5-widget': {
        root: ['CKEditor5', 'widget'],
        commonjs2: '@ckeditor/ckeditor5-widget',
        commonjs: '@ckeditor/ckeditor5-widget',
        amd: '@ckeditor/ckeditor5-widget'
      }
    }
  };

  module.exports.push(config);
});
