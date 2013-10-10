## **Portfolio Commons** ##


####Overview####
**Portfolio Commons** is a project which adds remote export functionality to the [Mahara](http://mahara.org) eportfolio software.

In addition to the local export options which are built into Mahara, **Portfolio Commons** provides remote export capability, for exporting html pages from Mahara directly to remote repositories, using the [SWORD](http://swordapp.org) protocol.

This makes it easy to deposit content into a remote repository without leaving the Mahara interface.


####Installation####

This project is a complete version of Mahara, which can be installed from scratch. It is based on the 1.7 Stable release of Mahara.

If updating an existing installation of Mahara -

1. Copy over the `sword` folder in the `export` folder in Mahara.

2. Copy over the `sword1` and `sword2` folders in the `lib` folder in Mahara.

3. Various other core files need to be added or edited to extend aspects of the Mahara interface in order to support **Portfolio Commons**. See the relevant commit in the **Portfolio Commons** repository for a list of added and edited files.


####Configuration####

The Mahara system administrator has to set up a password-protected account for each target repository, and add it to Mahara.
New repositories can be added and edited in the `sword` plugin configuration settings, in the Mahara Administration -> Extensions page.

The licence options are also editable in the same place. The [Creative Commons](http://creativecommons.org/licenses) licences are provided by default.

####Instructions For Use####

In Mahara, the link to export content to remote targets appears to the end user as a tab marked 'Remote' under Portfolio -> Export. (The link to local export options appears in the same place as a tab marked 'Local'.)

The user selects the pages they want to export (or their whole portfolio); a repository to deposit to; a collection in that repository; and an appropriate licence for the work.

**Please note**: when selecting pages to export, only publicly shared pages by the current user are made available for selection.

There is also an option to add Title and Description Metadata.

On submission, the html pages are zipped into a package together with a manifest file in the METS format, and the package is deposited into the remote repository.

The report from the remote repository is displayed to the user.


####Additional Notes####

**Portfolio Commons** is designed to work with SWORD 1 and SWORD 2 implementations.

It has been tested with [ePrints](http://eprints.org) and [DSpace](http://dspace.org) repositories. 

In our experience, SWORD implementation can vary considerably from repository to repository. At the moment this project is probably best considered as the basis of a link to a repository you have some degree of control over, rather than a universal solution.

After making a deposit to a remote repository, an attempt is made to retrieve a link to the Splash Page of the deposited package in the repository. Whether this succeeds or not is dependent on the repository's SWORD implementation. The link is read from the href value in the `<atom:link rel="alternate" />` element in the document returned after deposit.

Similarly, previewing of the deposited resource from the Splash Page is entirely dependent on the repository's implementation of a review mechanism. In a custom instance of ePrints we were able to provide live previews of the deposited html pages. 


####Fork it####
You are encouraged to fork this project and develop it further!


####Credits####

**Portfolio Commons** is A [JISC](http://jisc.ac.uk/) funded [OER Rapid Innovation](http://www.jisc.ac.uk/whatwedo/programmes/ukoer3/rapidinnovation.aspx) project.

Thanks to JISC for their support with this project. It wouldn't have happened otherwise.

Project blog: [Portfolio Commons](http://portfoliocommons.myblog.arts.ac.uk/)