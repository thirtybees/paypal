# Contributing to thirty bees
thirty bees is an open-source e-commerce solution, forked from PrestaShop. We'd like to encourage everyone to participate in the project, including contributing your own improvements to the source code.
 
## Procedure
 In order to contribute to this project, you need to be familiar with Git, an open source version control system used extensively by thirty bees developers, as well as GitHub:
 - A nice tutorial about Git can be found here: https://try.github.io/
 - GitHub help: https://help.github.com/
 
 Contributing to this project via Git can be done as follows:
 - Fork this project to your own GitHub account
 - Recursively clone this project to your local machine (use e.g. `git clone git@github.com:username/ThirtyBees.git --recursive`)
 - Optionally install the dependencies of this project to test it on your local machine (`composer install` in the root directory)
 - Create your own local branch (`git checkout -b mybranch`)
 - Make your changes
 - Commit (`git commit -m "Commit message"`). We do not have a commit message norm, but do make sure you use the [present tense](https://en.wikipedia.org/wiki/Present_tense)!
 - Then push the commit to your own fork (`git push -u origin mybranch`)
 - Visit either the fork or the thirty bees repository and GitHub should ask you to make a pull request. Follow this procedure and wait for one of our developers to include your changes into the codebase or tell you about possible improvements your pull request might need.  
 
 That's it. Thank you for your contribution!

## Licenses
Do not change the license headers of a file, except updating the copyright year.  
Files have either an [Open Software License 3.0 (OSL)](https://tldrlegal.com/license/open-software-licence-3.0) license (Core files) or an [Academic Free License 3.0 (AFL)](https://tldrlegal.com/license/academic-free-license-3.0-(afl)) (module files). If your contribution includes files with a different license your contribution cannot be accepted. If you do need to include a library for your improvement, add it to `composer.json`.  
By contributing to this project, you grant thirty bees a perpetual license on the content you submit to the project. This license implies granting use, modification, improvement, distribution and deletion of your contributions to the administrator of the project. It does not grant you the ability to request the removal of your contributions from the project. Contributing to this project implies that you are the author of the content or that you are authorized by the content author to submit these contributions to thirty bees. If your contributation adds an extra author field, changes the copyright or anything else that changes the software's license, the contribution will be rejected. Instead add your name to the `CONTRIBUTORS.md` file in the changeset. We'd love to add your name to it, after a successful merge!
