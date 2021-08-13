const fs = require('fs');
const path = require('path');
const minify = require('minify');

function minifyFiles (extension) {
  const assetsPath = path.resolve(`./views/${extension}`);
  const assetsFiles = fs.readdirSync(assetsPath);
  const isNotMinifiedAndHasSelectedExtension = filePath => filePath.includes(`.${extension}`) && !filePath.includes('.min');
  const filtredFiles = assetsFiles.filter(filePath => isNotMinifiedAndHasSelectedExtension(filePath));

  filtredFiles.forEach(file => {
    const filePath = path.resolve(`${assetsPath}/${file}`);

    minify(filePath, { js: { ecma: 6 }, css: { compatibility: '*' } })
      .then(minifiedContent => {
        const newFilePathName = filePath.split(`.${extension}`)[0].concat(`.min.${extension}`);
        fs.writeFileSync(newFilePathName, minifiedContent);
      })
      .catch(console.error);
  });
}

module.exports = { minifyFiles };
