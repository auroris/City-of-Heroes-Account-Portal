/****** Object:  Table [dbo].[user_email]    Script Date: 5/22/2019 5:11:22 PM ******/
/* Create this table in your cohauth database, or whatever equivalent you use */
USE [cohauth]
GO

CREATE TABLE [dbo].[user_email](
	[uid] [int] NOT NULL,
	[email] [nvarchar](250) NULL,
 CONSTRAINT [PK_user_email] PRIMARY KEY CLUSTERED ([uid] ASC)
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[user_email]  WITH CHECK ADD  CONSTRAINT [FK_user_email_user_account] FOREIGN KEY([uid])
REFERENCES [dbo].[user_account] ([uid])
ON DELETE CASCADE
GO

ALTER TABLE [dbo].[user_email] CHECK CONSTRAINT [FK_user_email_user_account]
GO