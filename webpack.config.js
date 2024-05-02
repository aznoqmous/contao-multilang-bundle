const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    entry: {
        "be": "./src/Resources/assets/be.js",
        "fe": "./src/Resources/assets/fe.js"
    },
    mode: "production",
    output: {
        filename: "[name].min.js",
        path: __dirname + "/src/Resources/public"
    },
    module: {
        rules: [
            {
                test: /\.scss$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                    },
                    { loader: "css-loader" },
                    { loader: "sass-loader" },
                ]
            }
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "[name].min.css",
            chunkFilename: "[id].min.css"
        })
    ]
};
