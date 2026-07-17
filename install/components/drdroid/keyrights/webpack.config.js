const webpack = require('webpack');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
  entry: './static/js/application.js',
  output: {
    filename: 'static/js/bundle.js',
    path: __dirname,
    clean: false
  },
  mode: 'production',
  devtool: false,
  plugins: [
    new webpack.DefinePlugin({
      'process.env.NODE_ENV': JSON.stringify('production')
    }),
    // The UI formats dates through moment's default locale. Do not ship all
    // 100+ optional locale files in the portal bundle.
    new webpack.IgnorePlugin({
      resourceRegExp: /^\.\/locale$/,
      contextRegExp: /moment$/
    })
  ],
  optimization: {
    minimize: true,
    minimizer: [new TerserPlugin({
      extractComments: false,
      terserOptions: {
        format: { comments: false },
        compress: { passes: 2 }
      }
    })]
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/,
        options: {
          presets: [
            ['@babel/preset-env', {targets: {ie: '11'}, modules: 'commonjs'}],
            ['@babel/preset-react', {runtime: 'classic'}]
          ]
        }
      }
    ]
  },

  devServer: {
    host: '0.0.0.0',
    port: 8032
  }
};
