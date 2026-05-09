terraform {
  backend "s3" {
    bucket = "my-lms-terraform-state"
    key    = "production/terraform.tfstate"
    region = "us-east-1"
  }
}