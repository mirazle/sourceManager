# sourceManager

◯ WEBHOOK_SERVER

(github.comからはapacheユーザーでアクセスがくる想定で環境構築する必要がある)

```html

// ssh-keygenして公開鍵をgithubに登録
sudo -u apache ssh-keygen -t rsa

// Gitマスターをcloneする
sudo -u apache git clone /usr/share/app/server/talkn/

// Svnリポジトリを用意
sudo -u apache svnadmin create /usr/share/app/svn/repos/talkn/

// Svnにコミット
sudo -u apache svn co file:///usr/share/app/svn/repos/talkn /usr/share/app/server/talkn/
sudo -u apache svn add /usr/share/app/svn/repos/talkn/*
sudo -u apache svn commit -m "initial" /usr/share/app/svn/repos/talkn/*
```
