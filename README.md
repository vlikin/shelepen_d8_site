# shelepen_d8_site

=== ISSUES ===
* The first vagrant up is not able to connect by ssh.

=== REPOSITORIES ===
* Subtree tutotial - http://blogs.atlassian.com/2013/05/alternatives-to-git-submodule-git-subtree/

git remote add -f drupal-core http://git.drupal.org/project/drupal.git
git remote add -f page_manager http://git.drupal.org/project/page_manager.git

git subtree add --prefix public_html drupal-core 8.0.x --squash
git subtree add --prefix public_html/modules/page_manager page_manager 8.x-1.x --squash


